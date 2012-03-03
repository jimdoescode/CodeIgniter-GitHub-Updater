<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2011 by Jim Saunders

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

class Github_updater
{
    const API_URL = 'https://api.github.com/';
    const GITHUB_URL = 'https://github.com/';
    const CONFIG_FILE = 'application/config/github_updater.php';

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->config('github_updater');
    }

    /**
     * Checks if the current version is up to date
     *
     * @return bool true if there is an update and false otherwise
     */
    public function has_update()
    {
        $branches = json_decode($this->_connect(self::API_URL.'repos/'.$this->ci->config->item('github_user').'/'.$this->ci->config->item('github_repo').'/branches'));
        return $branches[0]->commit->sha !== $this->ci->config->item('current_commit');
    }

    /**
     * Performs an update if one is available.
     *
     * @return bool true on success, false on failure
     */
    public function update()
    {
        $branches = json_decode($this->_connect(self::API_URL.'repos/'.$this->ci->config->item('github_user').'/'.$this->ci->config->item('github_repo').'/branches'));
        $hash = $branches[0]->commit->sha;
        if($hash !== $this->ci->config->item('current_commit'))
        {
            $commits = json_decode($this->_connect(self::API_URL.'repos/'.$this->ci->config->item('github_user').'/'.$this->ci->config->item('github_repo').'/compare/'.$this->ci->config->item('current_commit').'...'.$hash));
            $files = $commits->files;
            if($dir = $this->_get_and_extract($hash))
            {
                //Loop through the list of changed files for this commit
                foreach($files as $file)
                {
                    //If the file isn't in the ignored list then perform the update
                    if(!$this->_is_ignored($file->filename))
                    {
                        //If the status is removed then delete the file
                        if($file->status === 'removed')unlink($file->filename);
                        //Otherwise copy the file from the update.
                        else copy($dir.'/'.$file->filename, $file->filename);
                    }
                }
                //Clean up
                if($this->ci->config->item('clean_update_files'))
                {
                    shell_exec("rm -rf {$dir}");
                    unlink("{$hash}.zip");
                }
                //Update the current commit hash
                $this->_set_config_hash($hash);

                return true;
            }
        }
        return false;
    }

    private function _is_ignored($filename)
    {
        $ignored = $this->ci->config->item('ignored_files');
        foreach($ignored as $ignore)
            if(strpos($filename, $ignore) !== false)return true;

        return false;
    }

    private function _set_config_hash($hash)
    {
        $lines = file(self::CONFIG_FILE, FILE_IGNORE_NEW_LINES);
        $count = count($lines);
        for($i=0; $i < $count; $i++)
        {
            $configline = '$config[\'current_commit\']';
            if(strstr($lines[$i], $configline))
            {
                $lines[$i] = $configline.' = \''.$hash.'\';';
                $file = implode(PHP_EOL, $lines);
                $handle = @fopen(self::CONFIG_FILE, 'w');
                fwrite($handle, $file);
                fclose($handle);
                return true;
            }
        }
        return false;
    }

    private function _get_and_extract($hash)
    {
        copy(self::GITHUB_URL.$this->ci->config->item('github_user').'/'.$this->ci->config->item('github_repo').'/zipball/'.$this->ci->config->item('github_branch'), "{$hash}.zip");
        shell_exec("unzip {$hash}.zip");
        $files = scandir('.');
        foreach($files as $file)
            if(strpos($file, $this->ci->config->item('github_user').'-'.$this->ci->config->item('github_repo')) !== FALSE)return $file;

        return false;
    }

    private function _connect($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $response = curl_exec($ch);

        curl_close($ch);
        return $response;
    }
}
