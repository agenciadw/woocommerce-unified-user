<?php
/**
 * Plugin Name: DW User Management for WooCommerce
 * Plugin URI: https://github.com/agenciadw/woocommerce-unified-user
 * Author: David William da Costa
 * Author URI: https://github.com/agenciadw
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 6.4
 * WC requires at least: 6.0
 * Description: Gerencia nomes de usuário e exibição no WooCommerce/WordPress, forçando o formato Nome.Sobrenome e Nome Sobrenome após a finalização da compra, e permitindo atualização em massa para usuários existentes.
 * Version: 1.1
 * License: GPL v2 or later
 * Text Domain: woocommerce-unified-user
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// --- Funcionalidade para Novos Cadastros (ajustada para pós-finalização da compra) ---

// Remove os campos de Nome e Sobrenome do formulário de registro padrão do WooCommerce
// Estes campos serão tratados na finalização da compra
remove_action( 'woocommerce_register_form_start', 'wc_custom_registration_fields' );
remove_filter( 'woocommerce_registration_errors', 'wc_validate_custom_registration_fields', 10, 3 );

// Hook para reescrever o nome de usuário e nome de exibição após a finalização da compra
add_action( 'woocommerce_checkout_update_user_meta', 'wc_unified_update_user_after_checkout', 10, 2 );

function wc_unified_update_user_after_checkout( $customer_id, $data ) {
    $first_name = isset( $data['billing_first_name'] ) ? sanitize_text_field( $data['billing_first_name'] ) : '';
    $last_name = isset( $data['billing_last_name'] ) ? sanitize_text_field( $data['billing_last_name'] ) : '';

    if ( empty( $first_name ) || empty( $last_name ) ) {
        // Se nome ou sobrenome não estiverem disponíveis, não faz nada
        return;
    }

    // Atualiza os meta dados do usuário (first_name e last_name)
    update_user_meta( $customer_id, 'first_name', $first_name );
    update_user_meta( $customer_id, 'last_name', $last_name );
    update_user_meta( $customer_id, 'billing_first_name', $first_name );
    update_user_meta( $customer_id, 'billing_last_name', $last_name );

    // Gera o novo nome de usuário
    $suggested_username = sanitize_title( $first_name ) . '.' . sanitize_title( $last_name );
    $original_suggested_username = $suggested_username;
    $i = 1;
    while ( username_exists( $suggested_username ) && $suggested_username !== get_userdata( $customer_id )->user_login ) {
        $suggested_username = $original_suggested_username . $i;
        $i++;
    }

    // Gera o novo nome de exibição
    $new_display_name = trim( $first_name . ' ' . $last_name );

    // Atualiza o usuário
    wp_update_user( array(
        'ID'           => $customer_id,
        'user_login'   => $suggested_username,
        'display_name' => $new_display_name,
    ) );
}

// --- Funcionalidade para Atualização de Usuários Existentes ---

function dw_unified_user_management_menu() {
    add_management_page(
        'Gerenciar Usuários DW',
        'Gerenciar Usuários DW',
        'manage_options',
        'dw-unified-user-management',
        'dw_unified_user_management_page_content'
    );
}
add_action( 'admin_menu', 'dw_unified_user_management_menu' );

function dw_unified_user_management_page_content() {
    ?>
    <div class="wrap">
        <h1>Gerenciar Usuários DW</h1>
        <p>Esta ferramenta permite gerenciar os nomes de usuário e nomes de exibição no seu site WordPress/WooCommerce.</p>
        
        <h2>Atualização em Massa de Usuários Existentes</h2>
        <p>Clique no botão abaixo para iniciar a atualização dos nomes de usuário e nomes de exibição para o formato Nome.Sobrenome e Nome Sobrenome, respectivamente, para usuários existentes.</p>
        <p><strong>Atenção:</strong> Esta operação pode levar tempo dependendo do número de usuários e pode consumir recursos do servidor. Recomenda-se fazer um backup completo do seu site antes de prosseguir.</p>
        <form method="post" action="">
            <input type="hidden" name="dw_unified_user_management_action" value="run_update">
            <?php submit_button( 'Iniciar Atualização de Usuários Existentes', 'primary', 'dw_run_unified_update_button' ); ?>
        </form>
        <div id="dw-unified-user-management-log"></div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#dw_run_unified_update_button").on("click", function(e) {
                e.preventDefault();
                if (confirm("Tem certeza que deseja iniciar a atualização? Esta ação é irreversível.")) {
                    var button = $(this);
                    var logDiv = $("#dw-unified-user-management-log");
                    button.prop("disabled", true).val("Atualizando...");
                    logDiv.html("<p>Iniciando a atualização...</p>");

                    var offset = 0;
                    var batchSize = 20;

                    function processBatch() {
                        $.ajax({
                            url: ajaxurl,
                            type: "POST",
                            data: {
                                action: "dw_process_unified_user_batch",
                                offset: offset,
                                batch_size: batchSize,
                                _wpnonce: "<?php echo wp_create_nonce( 'dw_process_unified_user_batch_nonce' ); ?>"
                            },
                            success: function(response) {
                                logDiv.append(response.data.message);
                                if (response.data.has_more) {
                                    offset += batchSize;
                                    processBatch();
                                } else {
                                    button.prop("disabled", false).val("Atualização Concluída!");
                                    logDiv.append("<p>Atualização de usuários concluída.</p>");
                                }
                            },
                            error: function(xhr, status, error) {
                                logDiv.append("<p style=\"color: red;\">Erro: " + error + "</p>");
                                button.prop("disabled", false).val("Erro na Atualização!");
                            }
                        });
                    }
                    processBatch();
                }
            });
        });
    </script>
    <?php
}

add_action( 'wp_ajax_dw_process_unified_user_batch', 'dw_process_unified_user_batch_callback' );

function dw_process_unified_user_batch_callback() {
    check_ajax_referer( 'dw_process_unified_user_batch_nonce', '_wpnonce' );

    $offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
    $batch_size = isset( $_POST['batch_size'] ) ? intval( $_POST['batch_size'] ) : 20;

    $users = get_users( array(
        'number'  => $batch_size,
        'offset'  => $offset,
        'orderby' => 'ID',
        'order'   => 'ASC',
        'fields'  => array( 'ID', 'user_login', 'display_name' )
    ) );

    $message = '';
    $has_more = false;

    if ( ! empty( $users ) ) {
        foreach ( $users as $user ) {
            $user_id = $user->ID;
            $first_name = get_user_meta( $user_id, 'first_name', true );
            $last_name = get_user_meta( $user_id, 'last_name', true );

            if ( empty( $first_name ) || empty( $last_name ) ) {
                $message .= "<p>Usuário ID: " . $user_id . " - Nome ou sobrenome ausente. Pulando.</p>";
                continue;
            }

            // Gera o novo nome de usuário
            $new_username = sanitize_title( $first_name ) . '.' . sanitize_title( $last_name );
            $original_new_username = $new_username;
            $j = 1;
            while ( username_exists( $new_username ) && $new_username !== $user->user_login ) {
                $new_username = $original_new_username . $j;
                $j++;
            }

            // Gera o novo nome de exibição
            $new_display_name = trim( $first_name . ' ' . $last_name );

            // Atualiza o usuário se o nome de usuário ou nome de exibição for diferente
            if ( $new_username !== $user->user_login || $new_display_name !== $user->display_name ) {
                $update_args = array(
                    'ID'           => $user_id,
                    'user_login'   => $new_username,
                    'display_name' => $new_display_name,
                );

                $updated = wp_update_user( $update_args );

                if ( is_wp_error( $updated ) ) {
                    $message .= "<p style=\"color: red;\">Erro ao atualizar usuário ID: " . $user_id . " - " . $updated->get_error_message() . "</p>";
                } else {
                    $message .= "<p>Usuário ID: " . $user_id . " atualizado para username: " . $new_username . " e display name: " . $new_display_name . "</p>";
                }
            } else {
                $message .= "<p>Usuário ID: " . $user_id . " - Nome de usuário e exibição já estão corretos. Pulando.</p>";
            }
        }
        // Verifica se ainda há mais usuários para processar
        $total_users_count = count_users();
        if ( ( $offset + $batch_size ) < $total_users_count['total_users'] ) {
            $has_more = true;
        }
    }

    wp_send_json_success( array( 'message' => $message, 'has_more' => $has_more ) );
}


