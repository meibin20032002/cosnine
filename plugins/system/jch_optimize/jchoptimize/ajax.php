<?php

/**
 * JCH Optimize - Aggregate and minify external resources for optmized downloads
 * 
 * @author Samuel Marshall <sdmarshall73@gmail.com>
 * @copyright Copyright (c) 2010 Samuel Marshall
 * @license GNU/GPLv3, See LICENSE file
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */
defined('_JCH_EXEC') or die('Restricted access');

class JchOptimizeAjax
{

        /**
         * 
         * @param JchPlatformSettings $params
         */
        public static function garbageCron(JchPlatformSettings $params)
        {
                JchPlatformCache::gc();
        }

        ##<procode>##

        /**
         * 
         * @return type
         * @throws type
         */
        public static function optimizeImages()
        {
                error_reporting(0);

                $root = JchPlatformPaths::rootPath();

                set_time_limit(0);

                $dir_array = JchPlatformUtility::get('dir', '', 'array');
                $subdirs   = JchPlatformUtility::get('subdirs', '', 'array');
                $params    = (object) JchPlatformUtility::get('params', '', 'array');
                $task      = JchPlatformUtility::get('task', '0', 'string');

                if ($task == 'getfiles')
                {
			$dir = rtrim(JchPlatformUtility::decrypt($dir_array['path']), '/\\');
                        $files = array();

                        if (count(array_filter($subdirs)))
                        {
                                foreach ($subdirs as $subdir)
                                {
                                        $subdir = rtrim(JchPlatformUtility::decrypt($subdir), '/\\');
                                        $files  = array_merge($files, self::getImageFiles($root . $subdir, TRUE));
                                }
                        }

                        if (!empty($files))
                        {
                                $files = array_map(function($v)
                                {
                                        return JchOptimizeHelper::prepareImageUrl($v);
                                }, $files);
                        }

                        $data = array(
                                'files'    => $files,
                                'log_path' => JchPlatformUtility::getLogsPath()
                        );

                        return new JchOptimizeJson($data);
                }

                $options = array(
                        "files"  => array(),
                        "lossy" => true,//(bool) $params->kraken_optimization_level
			"resize" => array()
                );


		//Iterate through the packet of files
		foreach($dir_array as $file)
		{
			//Populate the files array with the file names
			$filepath = rtrim(JchPlatformUtility::decrypt($file['path']), '/\\'); 
			$options['files'][] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $filepath);

			//If resize dimensions are specified, save them in resize array using file path as index
			if (!empty($file['width']) || !empty($file['height']))
			{
				$filename = basename($filepath);
				$options['resize'][$filename]['width']  = (int) (!empty($file['width']) ? $file['width'] : 0);
				$options['resize'][$filename]['height'] = (int) (!empty($file['height']) ? $file['height'] : 0);
			}

			//Save backup of file
			if ($params->kraken_backup || !empty($options['resize'][$file['path']]))
			{
				$backup_file = self::getBackupFilename($filepath);
				self::copy($filepath, $backup_file);
			}

		}

                $oJchio = new JchOptimize\ImageOptimizer($params->pro_downloadid, $params->hidden_api_secret);

                $message = '';
		$datas = array();
		$data = null;

                try
                {
			//return an array of responses in the data property
                        $responses = $oJchio->upload($options);

			//Check if response if formatted properly
                        if (!isset($responses->success))
                        {
				self::logMessage($responses, 'Server error');

                                throw new Exception('Unrecognizable response from server', 500);
                        }

			//Handle responses that are exceptions (ie, codes 403, 500)
			if (!$responses->success)
			{
				self::logMessage($responses->message);

				throw new Exception($responses->message, $responses->code);
			}
			
			//Iterate through the array of data
			foreach($responses->data as $i => $response)
			{
				$original_file = $options['files'][$i];
				$message = $original_file . ': ';

				//Check if file was successfully optimized
				if ($response->success)
				{
					//Copy optimized file over original file
					if (self::copy($response->data->kraked_url, $original_file))
					{
						$message .= 'Optimized! You saved ' . $response->data->saved_bytes . ' bytes.';
					}
					else
					{
						//If copy failed
						$message .= 'Could not copy optimized file.';
						$data = new Exception($message, 404);
					}
				}
				else
				{
					//If file wasn't optimized format response accordingly
					$message .= $response->message;
					$data = new Exception($message, $response->code);
				}

				//Format each response
				$data = new JchOptimizeJson($data, $message);

				self::logMessage($data->message);

				//Save each response in the response array
				$datas[] = $data;
			}
                }
                catch (Exception $e)
                {
			//Save exceptions to datas variable in place of array.
			$datas = $e;
                }

		//Format Ajax response
                $respond = new JchOptimizeJson($datas);

                return $respond;
        }

	protected static function logMessage($message, $type='INFO')
	{
                try {
                        JchOptimizeLogger::logInfo($message, $type);
                }
                catch (Exception $e)
                {
                        
                }
	}

        /**
         * 
         * @param type $file
         * @return type
         */
        protected static function getBackupFilename($file)
        {
                $backup_parent_folder = JchPlatformPaths::backupImagesParentFolder();

                $backup_file = $backup_parent_folder . 'jch_optimize_backup_images/' .
                        str_replace(array(JchPlatformPaths::rootPath(), '_', '/'), array('', '', '_'), $file);

                if (@file_exists($backup_file))
                {
                        $backup_file = self::incrementBackupFileName($backup_file);
                }

                return $backup_file;
        }

        /**
         * 
         * @param type $file
         * @return type
         */
        protected function incrementBackupFileName($file)
        {
                $backup_file = preg_replace_callback('#(?:(_)(\d++))?(\.[^.\s]++)$#',
                                                     function($m)
                {
                        $m[1] = $m[1] == '' ? '_' : $m[1];
                        $m[2] = $m[2] == '' ? 0 : (int) $m[2];

                        return $m[1] . (string) ++$m[2] . $m[3];
                }, rtrim($file));

                if (@file_exists($backup_file))
                {
                        $backup_file = self::incrementBackupFileName($backup_file);
                }

                return $backup_file;
        }

        /**
         * 
         * @param type $scr
         * @param type $dest
         * @return type
         */
        public static function copy($src, $dest)
        {
                $dest_dir = dirname($dest);

                if (!@file_exists($dest_dir))
                {
                        JchPlatformUtility::createFolder($dest_dir);
                }

		if(!ini_get('allow_url_fopen'))
		{
			if(!preg_match('#^http#i', $src))
			{
				return copy($src, $dest);
			}

			//Open file handler.
			$fp = fopen($dest, 'wb');

			//If $fp is FALSE, something went wrong.
			if($fp === false){
				return false;
			}

			//Create a cURL handle.
			$ch = curl_init($src);

			//Pass our file handle to cURL.
			curl_setopt($ch, CURLOPT_FILE, $fp);

			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/libs/cacert.pem');

			//Execute the request.
			curl_exec($ch);

			//If there was an error, throw an Exception
			if($errno = curl_errno($ch)){
				return false;
			}

			//Get the HTTP status code.
			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			//Close the cURL handler.
			curl_close($ch);

			if($statusCode == 200){
				return true;
			} else{
				return false;
			}
		}

                $context = stream_context_create(array('ssl' => array(
                                'verify_peer' => true,
                                'cafile'      => dirname(__FILE__) . '/libs/cacert.pem'
                )));
                
                $src_stream = fopen($src, 'rb', false, $context);

                if ($src_stream === false)
                {
                        return false;
                }

                $dest_stream = fopen($dest, 'wb');

                return stream_copy_to_stream($src_stream, $dest_stream);
        }

        /**
         * 
         * @return string
         */
        public static function fileTree()
        {
                $root = rtrim(JchPlatformPaths::rootPath(), '/\\');

                $dir     = urldecode(JchPlatformUtility::get('dir', '', 'string', 'get'));
                $view    = urldecode(JchPlatformUtility::get('jchview', '', 'string', 'get'));
                $initial = urldecode(JchPlatformUtility::get('initial', '0', 'string', 'get'));

                $dir = JchPlatformUtility::decrypt($dir) . '/';

                if ($view != 'tree')
                {
                        $header  = '<div id="files-container-header"><ul class="jqueryFileTree"><li><span>&lt;root&gt;' . $dir . '</span></li>';
                        $header .= '<li class="check-all"><span><input type="checkbox"></span><span><em>Check all</em></span>'
                                . '<span><em>' . JchPlatformUtility::translate('Width') . ' (px)</em></span>'
                                . '<span><em>' . JchPlatformUtility::translate('Height') . ' (px)</em></span></li></ul></div><div class="files-content">';
                        $display = '';
                }
                else
                {
                        $display = 'style="display: none;"';
                        $header  = '';
                }

                $response = '';



                if (@file_exists($root . $dir))
                {
                        $files = scandir($root . $dir);
//                        $files = JchPlatformUtility::lsFiles($root . $dir, '\.(?:gif|jpe?g|png)$', FALSE);
                        natcasesort($files);
                        if (count($files) > 2)
                        { /* The 2 accounts for . and .. */
                                $response .= '';

                                $response = $header;

                                if ($initial && $view == 'tree')
                                {
                                        $response .= '<div class="files-content"><ul class="jqueryFileTree">';
                                        $response .= '<li class="directory expanded"><a href="#" rel="">&lt;root&gt;</a>';
                                }

                                $response .= '<ul class="jqueryFileTree" ' . $display . '>';

				$directories = array();
				$imagefiles = array();

                                foreach ($files as $file)
                                {
                                        if ($file != '.' && $file != '..' && is_dir($root . $dir . $file))
                                        {
                                                $directories[] = '<li class="directory collapsed">'
                                                        . self::item($file, $dir, $view, 'dir') . '</li>';
                                        }
                                // All files
					elseif ($view != 'tree' && preg_match('#\.(?:gif|jpe?g|png|GIF|JPE?G|PNG)$#', $file) && @file_exists($root . $dir . $file) )
					{
						$ext = preg_replace('/^.*\./', '', $file);
						$imagefiles[] = '<li class="file ext_' . strtolower($ext) . '">'
							. self::item($file, $dir, $view, 'file')
							. '</li>';

					}
                                }

                                $response .= implode('', $directories) . implode('', $imagefiles) . '</ul>';

                                if ($initial && $view == 'tree')
                                {
                                        $response .= '</li></ul></div>';
                                }
                        }
                }

                return $response;
        }

        /**
         * 
         * @param type $file
         * @param type $dir
         * @param type $view
         * @param type $path
         * @return string
         */
        private static function item($file, $dir, $view, $path)
        {
                $encrypt_dir  = JchPlatformUtility::encrypt($dir . $file);
                $encrypt_file = JchPlatformUtility::encrypt(rtrim(JchPlatformPaths::rootPath(), '/\\') . $dir . $file);

                $anchor = '<a href="#" rel="' . $encrypt_dir . '">'
                        . htmlentities($file)
                        . '</a>';

                $html = '';

                if ($view == 'tree')
                {
                        $html .= $anchor;
                }
                else
                {
                        if ($path == 'dir')
                        {
                                $html .= '<span><input type="checkbox" value="' . $encrypt_dir . '"></span>';
                                $html .= $anchor;
                        }
                        else
                        {
                                $html .= '<span><input type="checkbox" value="' . $encrypt_file . '"></span>';
                                $html .= '<span>' . htmlentities($file) . '</span>'
                                        . '<span><input type="text" size="10" maxlength="5" name="width" ></span>'
                                        . '<span><input type="text" size="10" maxlength="5" name="height" ></span>';
                        }
                }

                return $html;
        }

        private static function getImageFiles($dir, $recursive = false)
        {
                return JchPlatformUtility::lsFiles($dir, '\.(?:gif|jpe?g|png|GIF|JPE?G|PNG)$', $recursive);
        }

        ##</procode>##
}
