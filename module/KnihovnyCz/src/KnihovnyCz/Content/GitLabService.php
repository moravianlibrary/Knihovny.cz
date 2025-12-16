<?php

namespace KnihovnyCz\Content;

/**
 * Class GitLabService
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Content
 * @author   Pavel Pátek <pavel.patek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GitLabService
{
    /**
     * Constructor
     *
     * @param string|null $token     gitlab api token
     * @param string|null $apiUrl    gitlab api url
     * @param string|null $projectId gitlab project id
     * @param string|null $branch    gitlab branch
     * @param string|null $basePath  gitlab base path
     */
    public function __construct(
        private ?string $token = null,
        private ?string $apiUrl = null,
        private ?string $projectId = null,
        private ?string $branch = null,
        private ?string $basePath = null
    ) {
    }

    /**
     * Get last modified date of file
     *
     * @param string $fileName File name
     *
     * @return \DateTime|null
     *
     * @throws \DateMalformedStringException
     */
    public function getModifiedDate(string $fileName): ?\DateTime
    {
        $date = null;

        $filePath = $this->basePath . $fileName;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "PRIVATE-TOKEN: $this->token",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $url = $this->apiUrl . "projects/$this->projectId/repository/files/$filePath?ref={$this->getBranch()}";
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        $fileData = json_decode($response, true);

        if (isset($fileData['last_commit_id'])) {
            $commitId = $fileData['last_commit_id'];

            $commitUrl = $this->apiUrl . "projects/$this->projectId/repository/commits/$commitId";
            curl_setopt($ch, CURLOPT_URL, $commitUrl);
            $commitResponse = curl_exec($ch);
            $commitData = json_decode($commitResponse, true);

            if (isset($commitData['authored_date'])) {
                $date = new \DateTime($commitData['authored_date']);
            }
        }

        curl_close($ch);

        return $date;
    }

    /**
     * Token setter
     *
     * @param string|null $token gitlab api token
     *
     * @return GitLabService
     */
    public function setToken(?string $token): GitLabService
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Api Url setter
     *
     * @param string|null $apiUrl gitlab api url
     *
     * @return GitLabService
     */
    public function setApiUrl(?string $apiUrl): GitLabService
    {
        $this->apiUrl = $apiUrl;
        return $this;
    }

    /**
     * Project id setter
     *
     * @param string|null $projectId gitlab project id
     *
     * @return GitLabService
     */
    public function setProjectId(?string $projectId): GitLabService
    {
        $this->projectId = $projectId;
        return $this;
    }

    /**
     * Branch setter
     *
     * @param string|null $branch gitlab branch
     *
     * @return GitLabService
     */
    public function setBranch(?string $branch): GitLabService
    {
        $this->branch = $branch;
        return $this;
    }

    /**
     * Base path setter
     *
     * @param string|null $basePath gitlab base path
     *
     * @return GitLabService
     */
    public function setBasePath(?string $basePath): GitLabService
    {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * Branch getter
     *
     * @return string
     */
    public function getBranch(): string
    {
        return $this->branch ?: 'HEAD';
    }
}
