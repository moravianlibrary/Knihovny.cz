<?php
declare(strict_types = 1);
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

class ZiskejApiFactory implements FactoryInterface
{
    /**
     * Create Ziskej Api service
     *
     * @param \Interop\Container\ContainerInterface $container
     * @param string                                $requestedName
     * @param array|null                            $options
     *
     * @return \Mzk\ZiskejApi\Api
     * @throws \Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Api
    {
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
        $privateKey = Key\LocalFileReference::file('file://' . $cpkZiskej->getPrivateKeyFileLocation());

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
            new ApiClient($guzzleClient, $cpkZiskej->getCurrentUrl(), new Bearer($token->toString()), $logger)
        );
    }
}
