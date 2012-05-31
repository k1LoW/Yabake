<?php

App::uses('AppShell', 'Console/Command');
App::uses('TemplateTask', 'Console/Command/Task');
App::uses('Folder', 'Utility');

class FormTemplateTask extends TemplateTask {

    protected function _findThemes() {
        $paths = array();
        $core = current(App::path('Console', 'Yabake'));
        $separator = DS === '/' ? '/' : '\\\\';
        $core = preg_replace('#shells' . $separator . '$#', '', $core);

        $Folder = new Folder($core . 'Templates' . DS . 'form');

        $contents = $Folder->read();
        $themeFolders = $contents[0];

        $plugins = App::objects('plugin');
        $paths[] = $core;
        foreach ($plugins as $plugin) {
            $paths[] = $this->_pluginPath($plugin) . 'Console' . DS;
        }

        $paths = array_merge($paths, App::path('Console'));

        // TEMPORARY TODO remove when all paths are DS terminated
        foreach ($paths as $i => $path) {
            $paths[$i] = rtrim($path, DS) . DS;
        }
        $themes = array();
        foreach ($paths as $path) {
            $Folder = new Folder($path . 'Templates', false);
            $contents = $Folder->read();
            $subDirs = $contents[0];
            foreach ($subDirs as $dir) {
                if (empty($dir) || preg_match('@^skel$|_skel$@', $dir)) {
                    continue;
                }
                $Folder = new Folder($path . 'Templates' . DS . $dir);
                $contents = $Folder->read();
                $subDirs = $contents[0];
                if (array_intersect($contents[0], $themeFolders)) {
                    $templateDir = $path . 'Templates' . DS . $dir . DS;
                    $themes[$dir] = $templateDir;
                }
            }
        }
        return $themes;
    }

}
