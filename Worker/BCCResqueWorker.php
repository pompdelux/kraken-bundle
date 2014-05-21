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
     * Setup the job
     *
     * @param string $client Kranken client service id
     * @param string $source Public http(s) url to source image
     * @param string $target Target directory
     * @param array $sizes   Array of size dimensions.
     */
    public function setup($client, $source, $target, $sizes = [])
    {
        $this->args['kraken'] = [
            'client' => $client,
            'source' => $source,
            'target' => $target,
            'sizes'  => $sizes
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function run($args = [])
    {
        if (count($this->args['kraken']['sizes'])) {
            foreach ($this->args['kraken']['sizes'] as $size) {
                $this->handleOne($size);
            }

            return;
        }

        $this->handleOne();
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

        if (true === $response['success']) {
            return $this->saveFile($response, $size);
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

        return (bool) $bytes;
    }
}
