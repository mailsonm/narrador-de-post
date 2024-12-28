<?php
/**
 * Plugin Name: Narrador-de-Post
 * Plugin URI:  https://github.com/mailsonm
 * Description: Widget de conversão de texto em fala para posts do WordPress usando o Read Aloud Widget.
 * Version:     1.1.0
 * Author:      mailsonm
 * Author URI:  (Seu site ou perfil)
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: narrador-de-post
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Adiciona o botão de narração no início do conteúdo.
function narrador_de_post_adicionar_botao($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $botao_html = '<div id="narrador-de-post">
            <button onclick="narrarPost()">🔊 Ouvir este artigo</button>
            <audio id="narrador-audio" controls style="display:none;"></audio>
        </div>';
        $content = $botao_html . $content;
    }
    return $content;
}
add_filter('the_content', 'narrador_de_post_adicionar_botao');

// Adiciona o script JavaScript necessário.
function narrador_de_post_incluir_scripts() {
    if (is_single()) {
        wp_enqueue_script('narrador-de-post', plugin_dir_url(__FILE__) . 'narrador-de-post.js', array(), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'narrador_de_post_incluir_scripts');

// Adiciona o CSS necessário para o botão de narração.
function narrador_de_post_incluir_estilo() {
    if (is_single()) {
        wp_enqueue_style('narrador-de-post-style', plugin_dir_url(__FILE__) . 'narrador-de-post.css');
    }
}
add_action('wp_enqueue_scripts', 'narrador_de_post_incluir_estilo');
