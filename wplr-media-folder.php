<?php
/*
Plugin Name: WP Media Folder for Lightroom
Description: WP Media Folder Extension for Lightroom through the WP/LR Sync plugin.
Version: 0.1.3
Author: Jordy Meow
Author URI: http://www.meow.fr
*/

class WPLR_Extension_MediaFolder {

  public function __construct() {

    // Init
    add_action( 'init', array( $this, 'init' ), 10, 0 );
    add_filter( 'wplr_extensions', array( $this, 'extensions' ), 10, 1 );

    // Create / Update
    add_action( 'wplr_create_folder', array( $this, 'create_folder' ), 10, 3 );
    add_action( 'wplr_create_collection', array( $this, 'create_folder' ), 10, 3 );
    add_action( 'wplr_update_folder', array( $this, 'update_folder' ), 10, 2 );
    add_action( 'wplr_update_collection', array( $this, 'update_folder' ), 10, 2 );
    add_action( "wplr_move_folder", array( $this, 'move_folder' ), 10, 3 );
    add_action( "wplr_move_collection", array( $this, 'move_folder' ), 10, 3 );
    add_action( "wplr_remove_folder", array( $this, 'remove_folder' ), 10, 1 );
    add_action( "wplr_remove_collection", array( $this, 'remove_folder' ), 10, 1 );
    add_action( "wplr_add_media_to_collection", array( $this, 'add_media_to_folder' ), 10, 2 );
    add_action( "wplr_remove_media_from_collection", array( $this, 'remove_media_from_folder' ), 10, 2 );

    // Extra
    //add_action( 'wplr_reset', array( $this, 'reset' ), 10, 0 );
    //add_action( "wplr_clean", array( $this, 'clean' ), 10, 1 );
    //add_action( "wplr_remove_media", array( $this, 'remove_media' ), 10, 1 );
  }

  function init( ) {
    // Create the taxonomy if Media Folder doesn't create it (basically it doesn't create it if we are not in the admin)
    if ( !taxonomy_exists( 'wpmf-category' ) ) {
      register_taxonomy( 'wpmf-category', 'attachment', array( 'hierarchical' => true, 'show_in_nav_menus' => false, 'show_ui' => false ) );
    }
  }

  function extensions( $extensions ) {
    array_push( $extensions, 'WP Media Folder' );
    return $extensions;
  }

  function create_folder( $folderId, $inFolderId, $folder ) {
    global $wplr;
    $parentTermId = $wplr->get_meta( "mediafolder_term_id", $inFolderId );
    $result = wp_insert_term( $folder['name'], 'wpmf-category', array( 'parent' => $parentTermId ) );
    if ( is_wp_error( $result ) ) {
      error_log( "Issue while creating the folder " . $folder['name'] . "." );
      error_log( $result->get_error_message() );
      return;
    }
    $wplr->set_meta( 'mediafolder_term_id', $folderId, $result['term_id'] );
  }

  // Updated the folder with new information.
  // Currently, that would be only its name.
  function update_folder( $folderId, $folder ) {
    global $wplr;
    $termId = $wplr->get_meta( "mediafolder_term_id", $folderId );
    $result = wp_update_term( (int)$termId, 'wpmf-category', array( 'name' => $folder['name'] ) );
    if ( is_wp_error( $result ) ) {
      error_log( "Issue while updating the folder " . $folder['name'] . "." );
      error_log( $result->get_error_message() );
      return;
    }
  }

  // Move the folder.
  function move_folder( $folderId, $inFolderId ) {
    global $wplr;
    $termId = $wplr->get_meta( "mediafolder_term_id", $folderId );
    $parentTermId = $wplr->get_meta( "mediafolder_term_id", $inFolderId );
    $result = wp_update_term( $termId, 'wpmf-category', array( 'parent' => $parentTermId ) );
    if ( is_wp_error( $result ) ) {
      error_log( "Issue while moving the folder." );
      error_log( $result->get_error_message() );
      return;
    }
  }

  // Added meta to a collection.
  // The $mediaId is actually the WordPress Post/Attachment ID.
  function add_media_to_folder( $mediaId, $collectionId ) {
    global $wplr;
    $termId = $wplr->get_meta( "mediafolder_term_id", $collectionId );
    error_log( "add_media_to_folder( $mediaId, $collectionId ) - $termId" );
    $result = wp_set_post_terms( $mediaId, array( (int)$termId ), 'wpmf-category' );
    if ( is_wp_error( $result ) ) {
      error_log( "Issue while adding the media to the collection." );
      error_log( $result->get_error_message() );
      return;
    }
  }

  // Remove media from the collection.
  function remove_media_from_folder( $mediaId, $collectionId ) {
    global $wplr;
    $termId = $wplr->get_meta( "mediafolder_term_id", $collectionId );
    $result = wp_remove_object_terms( $mediaId, (int)$termId, 'wpmf-category' );
    if ( is_wp_error( $result ) ) {
      error_log( "Issue while removing the media from the collection." );
      error_log( $result->get_error_message() );
      return;
    }
  }

  // Delete the folder.
  function remove_folder( $folderId ) {
    global $wplr;
    $termId = $wplr->get_meta( "mediafolder_term_id", $folderId );
    $result = wp_delete_term( $termId, 'wpmf-category' );
    if ( is_wp_error( $result ) ) {
      error_log( "Issue while removing the folder." );
      error_log( $result->get_error_message() );
      return;
    }
    $wplr->delete_meta( 'mediafolder_term_id', $folderId );
  }
}

new WPLR_Extension_MediaFolder;

?>
