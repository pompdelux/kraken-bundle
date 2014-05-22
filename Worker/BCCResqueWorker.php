<?php
/*
 * This file is part of the hanzo package.
 *
 * (c) Ulrik Nielsen <un@bellcom.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pompdelux\KrakenBundle\Worker;

use BCC\ResqueBundle\ContainerAwareJob;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class BCCResqueWorker extends ContainerAwareJob
{
    public $queue = 'kraken';

    /**
     * Construct, if parameters is not present, we are most likely in the job run process.
     *
     * @param string $client        Kranken client service id
     * @param string $source        Public http(s) url to source image
     * @param string $target        Target directory
     * @param bool   $delete_source If set to true, the source file is deleted on success.
     * @param array  $sizes         Array of size dimensions.
     */
    public function __construct($client = null, $source = null, $target = null, $delete_source = false, $sizes = [])
    {
        if (!empty($client) &&
            !empty($source) &&
            !empty($target)
        ) {
            $this->args['kraken'] = [
                'client' => $client,
                'source' => $source,
                'target' => $target,
                'delete_source' => (bool) $delete_source,
                'sizes'  => $sizes
            ];
        }
    }


    /**
     * {@inheritdoc}
     */
    public function run($args = [])
    {
        if (0 == count($this->args['kraken']['sizes'])) {
            return $this->handleOne();
        }

        foreach ($this->args['kraken']['sizes'] as $size) {
            $this->handleOne($size);
        }
    }


    /**
     * Handle Kraken call pr size/file
     *
     * @param  array $size
     * @return bool
     */
    protected function handleOne($size = [])
    {
        static $kraken;

        $args = $this->args['kraken'];

        if (empty($kraken)) {
            $kraken = $this->getContainer()->get($args['client']);
        }

        if (empty($size)) {
            $response = $kraken->squeeze($args['source']);
        } else {
            $response = $kraken->resize($args['source'], [$size])[0];
        }

        if ($response->getStatus()) {
            return $this->saveFile($response->getResponse(), $size);
        }

        return false;
    }


    /**
     * Save processed file to disk.
     *
     * @param  array $response
     * @param  array $size
     * @return bool
     * @throws FileException
     */
    protected function saveFile($response, $size = [])
    {
        $args = $this->args['kraken'];
        $destination = rtrim($args['target'], '/').'/';

        if (!is_writable($destination)) {
            throw new FileException("Destination: '".$destination."' is not writable.");
        }

        if (isset($size['width'], $size['height'])) {
            $destination .= $size['width'].'x'.$size['height'].','.$response['file_name'];
        } else {
            $destination .= $response['file_name'];
        }

        $in  = fopen($response['kraked_url'], 'r');
        $out = fopen($destination, 'wb+');

        if (!$in || !$out) {
            throw new FileException("File handler failed, cannot save kraken file.");
        }

        $bytes = stream_copy_to_stream($in, $out);

        fclose($in);
        fclose($out);

        if ($bytes) {
            $this->deleteFile();
        }

        return (bool) $bytes;
    }

    /**
     * Delete the original source file.
     *
     * @return bool|void
     */
    protected function deleteFile()
    {
        if ($this->args['kraken']['delete_source']) {
            return unlink($this->args['kraken']['source']);
        }
    }
}
