<?php
/**
 * Roles: register `jugador`, remove unused WP defaults, grant custom FMDB
 * capabilities, gate wp-admin access for player-facing roles.
 *
 * Custom capabilities (checked elsewhere in the theme):
 *   fmdb_manage_teams        — can manage any team (admin + editor_fmdb)
 *   fmdb_manage_affiliations — can approve/reject user affiliations (admin only)
 *
 * Note: roles `representante_equipo` and `editor_fmdb` are referenced
 * elsewhere in the theme but are managed by the Members plugin (or were
 * created via WP admin) rather than registered here.
 */

// Register custom roles, remove unused WP defaults, grant FMDB caps
add_action( 'init', function () {
    if ( ! get_role( 'jugador' ) ) {
        add_role( 'jugador', 'Jugador', [ 'read' => true ] );
    }
    foreach ( [ 'subscriber', 'contributor', 'author', 'editor' ] as $r ) {
        remove_role( $r );
    }

    $admin = get_role( 'administrator' );
    if ( $admin && ! $admin->has_cap( 'fmdb_manage_teams' ) ) {
        $admin->add_cap( 'fmdb_manage_teams' );
        $admin->add_cap( 'fmdb_manage_affiliations' );
    }

    $editor = get_role( 'editor_fmdb' );
    if ( $editor && ! $editor->has_cap( 'fmdb_manage_teams' ) ) {
        $editor->add_cap( 'fmdb_manage_teams' );
        $editor->add_cap( 'fmdb_manage_affiliations' );
    }
    if ( $editor && ! $editor->has_cap( 'list_users' ) ) {
        $editor->add_cap( 'list_users' );
        $editor->add_cap( 'edit_users' );
    }
    if ( $editor && $editor->has_cap( 'edit_pages' ) ) {
        foreach ( [
            'edit_pages', 'edit_others_pages', 'edit_published_pages',
            'edit_private_pages', 'read_private_pages',
            'delete_pages', 'delete_others_pages', 'delete_published_pages',
            'delete_private_pages', 'publish_pages',
        ] as $cap ) {
            $editor->remove_cap( $cap );
        }
    }
} );

// Block all WP admin access for jugadores and representantes — front-end only
add_action( 'admin_init', function () {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
    $user  = wp_get_current_user();
    $roles = (array) $user->roles;
    if ( array_intersect( $roles, [ 'jugador', 'representante_equipo' ] ) ) {
        wp_safe_redirect( home_url( '/mi-equipo/' ) );
        exit;
    }
} );

// Show admin bar only for roles that use wp-admin (admin + editor_fmdb)
add_action( 'after_setup_theme', function () {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        show_admin_bar( false );
    }
} );
