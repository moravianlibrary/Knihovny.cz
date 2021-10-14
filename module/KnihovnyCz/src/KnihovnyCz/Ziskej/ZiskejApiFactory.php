<?php
declare(strict_types = 1);
/**
 * Class ZiskejApiFactory
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Ziskej
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Ziskej;

use Http\Message\Authentication\Bearer;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\MultibyteStringConverter;
use Lcobucci\JWT\Signer\Ecdsa\Sha512;
use Lcobucci\JWT\Signer\Key;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Mzk\ZiskejApi\Api;
use Mzk\ZiskejApi\ApiClient;

/**
 * Class ZiskejApiFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Ziskej
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejApiFactory implements FactoryInterface
{
    /**
     * Create Ziskej Api service
     *
     * @param ContainerInterface $container     DI container
     * @param string             $requestedName Service name
     * @param array|null         $options       Service options
     *
     * @return \Mzk\ZiskejApi\Api
     * @throws \Exception
     */
    public function __invoke(
        ContainerInterface $container, $requestedName, array $options = null
    ): Api {
        $cpkZiskej = $container->get(\KnihovnyCz\Ziskej\ZiskejMvs::class);

        $config = $container->get('VuFind\Config')->get('config');

        $logger = new Logger('ZiskejApi');

        $handlerStream = new StreamHandler('log/ziskej-api.log', $logger::DEBUG);
        $logger->pushHandler($handlerStream);

        if (!empty($config->Filebeat) && !empty($config->Filebeat->host)) {
            $connectionString = $config->Filebeat->host;
            if (!empty($config->Filebeat->port)) {
                $connectionString .= ':' . $config->Filebeat->port;
            }
            $handlerSocket = new SocketHandler($connectionString);
            $formaterJson = new JsonFormatter();
            $handlerSocket->setFormatter($formaterJson);
            $logger->pushHandler($handlerSocket);
        }
        $httpService = $container->get(\KnihovnyCz\Service\GuzzleHttpService::class);
        $guzzleClient = $httpService->createClient(
            [
                'connect_timeout' => 10,
            ]
        );

        // generate token
        $signer = new Sha512(new MultibyteStringConverter());
        $privateKey = Key\LocalFileReference::file(
            'file://' . $cpkZiskej->getPrivateKeyFileLocation()
        );

        $config = Configuration::forSymmetricSigner(
            $signer,
            $privateKey
        );

        $token = $config->builder()
            ->issuedBy('cpk')
            ->issuedAt((new \DateTimeImmutable())->setTimestamp(time()))
            ->expiresAt((new \DateTimeImmutable())->setTimestamp(time() + 3600))
            ->withClaim('app', 'cpk')
            ->getToken($signer, $privateKey);

        //@todo store token

        return new Api(
            new ApiClient(
                $guzzleClient,
                $cpkZiskej->getCurrentUrl(),
                new Bearer($token->toString()),
                $logger
            )
        );
    }
}