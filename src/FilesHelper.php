<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveArrayIterator;
use RecursiveDirectoryIterator;

class FilesHelper {

    /**
     * Recursively delete a directory
     *
     * @throws WP2StaticException
     */
    public static function delete_dir_with_files( string $dir ) : void {
        if ( is_dir( $dir ) ) {
            $dir_files = scandir( $dir );

            if ( ! $dir_files ) {
                $err = 'Trying to delete nonexistant dir: ' . $dir;
                WsLog::l( $err );
                throw new WP2StaticException( $err );
            }

            $files = array_diff( $dir_files, [ '.', '..' ] );

            foreach ( $files as $file ) {
                ( is_dir( "$dir/$file" ) ) ?
                self::delete_dir_with_files( "$dir/$file" ) :
                unlink( "$dir/$file" );
            }

            rmdir( $dir );
        }
    }

    /**
     * Get public URLs for all files in a local directory
     *
     * @return string[] list of URLs
     */
    public static function getListOfLocalFilesByDir( string $dir ) : array {
        $files = [];

        $site_path = SiteInfo::getPath( 'site' );

        if ( is_dir( $dir ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $dir,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable = self::filePathLooksCrawlable( $filename );

                if ( $path_crawlable ) {
                    if ( is_string( $site_path ) ) {
                        $url = str_replace( $site_path, '/', $filename );

                        if ( is_string( $url ) ) {
                            $files[] = $url;
                        }
                    }
                }
            }
        }

        return $files;
    }

    public static function filePathLooksCrawlable( string $file_name ) : bool {
        $filenames_to_ignore = [
            '.DS_Store',
            '.PHP',
            '.SQL',
            '.crt',
            '.git',
            '.idea',
            '.ini',
            '.less',
            '.map',
            '.md',
            '.mo',
            '.mo',
            '.php',
            '.php',
            '.phtml',
            '.po',
            '.po',
            '.pot',
            '.scss',
            '.sh',
            '.sh',
            '.sql',
            '.tar.gz',
            '.tpl',
            '.txt',
            '.yarn',
            '.zip',
            '__MACOSX',
            'backwpup',
            'bower.json',
            '.htaccess',
            'wc-logs',
            'bower_components',
            'composer.json',
            'current-export',
            'gulpfile.js',
            'latest-export',
            'node_modules',
            'package.json',
            'pb_backupbuddy',
            'previous-export',
            'thumbs.db',
            'tinymce',
            'wp-static-html-output', // exclude earlier version exports
            'wp2static-crawled-site',
            'wp2static-processed-site',
            'wp2static-addon',
            'LICENSE',
            'README',
            'static-html-output-plugin',
            'wp2static-working-files',
            'wpallexport',
            'wpallimport',
        ];

        $matches = 0;

        str_replace( $filenames_to_ignore, '', $file_name, $matches );

        if ( $matches > 0 ) {
            return false;
        }

        return true;
    }

    /**
     * Clean all detected URLs before use
     *
     * @param string[] $urls list of URLs
     * @return string[] list of URLs
     * @throws WP2StaticException
     */
    public static function cleanDetectedURLs( array $urls ) : array {
        $home_url = SiteInfo::getUrl( 'home' );

        if ( ! is_string( $home_url ) ) {
            $err = 'Home URL not defined ';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $cleaned_urls = array_map(
            // trim hashes/query strings
            function ( $url ) use ( $home_url ) {
                if ( ! $url ) {
                    return;
                }

                // NOTE: 2 x str_replace's significantly faster than
                // 1 x str_replace with search/replace arrays of 2 length
                $url = str_replace(
                    $home_url,
                    '/',
                    $url
                );

                $url = str_replace(
                    '//',
                    '/',
                    $url
                );

                if ( ! is_string( $url ) ) {
                    return;
                }

                $url = strtok( $url, '#' );

                if ( ! $url ) {
                    return;
                }

                $url = strtok( $url, '?' );

                return $url;
            },
            $urls
        );

        return $cleaned_urls;
    }
}

