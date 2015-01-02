<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 09/11/14
 * Time: 00:46
 */

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;
use dft\FoapiBundle\Traits\Database;
use dft\FoapiBundle\Traits\Logger;


/**
 * Class Image
 * @package dft\FoapiBundle\Services
 */
class Image {
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Uploads a file for a user.
     * TODO: Add security checks.
     * @param $userId
     * @param $fileName
     * @param $fileContent Raw file content.
     * @param $mimeType
     */
    public function upload($userId, $fileName, $fileContent, $mimeType) {
        $query = "INSERT INTO images SET user_id = ?, name = ?, content = ?, mime_type = ?";
        // Encode in base 64.
        $fileContent = base64_encode($fileContent);

        $statement = $this->prepare($query);
        $statement->bindValue(1, $userId);
        $statement->bindValue(2, $fileName);
        $statement->bindValue(3, $fileContent);
        $statement->bindValue(4, $mimeType);

        $statement->execute();
    }

    /**
     * Lists all images for a given user.
     *
     * @param $userId
     * @return array
     */
    public function fetchAll($userId) {
        $query = "SELECT id, mime_type, name, link, type FROM images WHERE user_id IN (?)";
        $statement = $this->prepare($query);
        $userIds = $this->constructUserIdsIn($userId);
        $statement->bindValue(1, $userIds);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Deletes an image.
     * @param $userId
     * @param $imageId
     */
    public function delete($userId, $imageId) {
        $query = "DELETE FROM images WHERE user_id IN (?) AND id = ?";
        $statement = $this->prepare($query);
        $userIds = $this->constructUserIdsIn($userId);
        $statement->bindValue(1, $userIds);
        $statement->bindValue(2, $imageId);
        $statement->execute();
    }

    /**
     * Returns an image.
     * @param $userId
     * @param $imageId
     * @return mixed
     */
    public function fetchOne($userId, $imageId) {
        $query = "SELECT * FROM images WHERE user_id IN (?) AND id = ? LIMIT 1";
        $statement = $this->prepare($query);
        $userIds = $this->constructUserIdsIn($userId);
        $statement->bindValue(1, $userIds);
        $statement->bindValue(2, $imageId);
        $statement->execute();
        $image = $statement->fetchAll();
        // Decode.
        $image[0]['content'] = base64_decode($image[0]['content']);

        return $image[0];
    }

    /**
     * Updates image properties.
     * @param $userId
     * @param $imageId
     * @param $link
     * @param $type 1 - Logo, 2, 3, 4 - Fact 1, 2, 3
     */
    public function update($userId, $imageId, $link, $type) {
        $query = "UPDATE images SET link = ?, type = ? WHERE user_id IN(?) AND id = ?";
        $statement = $this->prepare($query);
        $userIds = $this->constructUserIdsIn($userId);
        $statement->bindValue(1, $link);
        $statement->bindValue(2, $type);
        $statement->bindValue(3, $userIds);
        $statement->bindValue(4, $imageId);
        $statement->execute();
    }
}
