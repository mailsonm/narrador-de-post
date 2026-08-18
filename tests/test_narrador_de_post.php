<?php
/**
 * Testes Unitários para o Plugin Narrador de Post (v2.0.0 Híbrido)
 * Execução: docker run --rm -v "${PWD}:/app" -w /app php:8.3-cli php tests/test_narrador_de_post.php
 */

// Mock do ambiente WordPress
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

$wp_actions = [];
$wp_filters = [];
$wp_shortcodes = [];
$wp_options = [];
$wp_post_meta = [];
$wp_post_types = [
    'post' => (object) [ 'name' => 'post', 'labels' => (object) [ 'singular_name' => 'Post' ] ],
    'page' => (object) [ 'name' => 'page', 'labels' => (object) [ 'singular_name' => 'Page' ] ],
];
$current_post_type = 'post';
$current_post_id = 42;
$is_single_val = true;
$is_feed_val = false;
$is_main_query_val = true;
$in_the_loop_val = true;

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
    global $wp_actions;
    $wp_actions[$tag][] = $callback;
}

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
    global $wp_filters;
    $wp_filters[$tag][] = $callback;
}

function add_shortcode( $tag, $callback ) {
    global $wp_shortcodes;
    $wp_shortcodes[$tag] = $callback;
}

function do_shortcode( $content ) {
    global $wp_shortcodes;
    foreach ( $wp_shortcodes as $tag => $callback ) {
        if ( strpos( $content, "[{$tag}" ) !== false ) {
            $content = preg_replace_callback( "/\[{$tag}(.*?)\]/", function( $matches ) use ( $callback ) {
                return call_user_func( $callback, [] );
            }, $content );
        }
    }
    return $content;
}

function get_option( $name, $default = false ) {
    global $wp_options;
    return $wp_options[$name] ?? $default;
}

function update_option( $name, $value ) {
    global $wp_options;
    $wp_options[$name] = $value;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
    global $wp_post_meta;
    if ( isset( $wp_post_meta[$post_id][$key] ) ) {
        return $single ? $wp_post_meta[$post_id][$key] : [ $wp_post_meta[$post_id][$key] ];
    }
    return $single ? '' : [];
}

function update_post_meta( $post_id, $key, $value ) {
    global $wp_post_meta;
    $wp_post_meta[$post_id][$key] = $value;
}

function get_post_type( $post = null ) {
    global $current_post_type;
    return $current_post_type;
}

function get_the_ID() {
    global $current_post_id;
    return $current_post_id;
}

function is_singular( $post_types = '' ) {
    global $is_single_val, $current_post_type;
    if ( ! $is_single_val ) return false;
    if ( empty( $post_types ) ) return true;
    if ( is_array( $post_types ) ) return in_array( $current_post_type, $post_types, true );
    return $current_post_type === $post_types;
}

function is_single() {
    global $is_single_val;
    return $is_single_val;
}

function is_feed() {
    global $is_feed_val;
    return $is_feed_val;
}

function is_main_query() {
    global $is_main_query_val;
    return $is_main_query_val;
}

function in_the_loop() {
    global $in_the_loop_val;
    return $in_the_loop_val;
}

function get_post_types( $args = [], $output = 'names' ) {
    global $wp_post_types;
    return $wp_post_types;
}

function esc_attr( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
    return filter_var( (string) $url, FILTER_SANITIZE_URL );
}

function sanitize_text_field( $str ) {
    return strip_tags( trim( (string) $str ) );
}

function sanitize_key( $key ) {
    return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $key ) );
}

function sanitize_html_class( $class, $fallback = '' ) {
    $c = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
    return empty( $c ) ? $fallback : $c;
}

function absint( $maybeint ) {
    return abs( intval( $maybeint ) );
}

function __( $text, $domain = 'default' ) {
    return $text;
}

function esc_html__( $text, $domain = 'default' ) {
    return esc_html( $text );
}

function esc_attr_e( $text, $domain = 'default' ) {
    echo esc_attr( $text );
}

function esc_html_e( $text, $domain = 'default' ) {
    echo esc_html( $text );
}

function selected( $selected, $current = true, $echo = true ) {
    $result = ( (string) $selected === (string) $current ) ? "selected='selected'" : '';
    if ( $echo ) echo $result;
    return $result;
}

function checked( $checked, $current = true, $echo = true ) {
    $result = ( (string) $checked === (string) $current ) ? "checked='checked'" : '';
    if ( $echo ) echo $result;
    return $result;
}

function strip_shortcodes( $content ) {
    return preg_replace( '/\[.*?\]/', '', $content );
}

function wp_strip_all_tags( $string, $remove_breaks = false ) {
    $string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
    $string = strip_tags( $string );
    if ( $remove_breaks ) {
        $string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
    }
    return trim( $string );
}

function plugin_basename( $file ) {
    return basename( $file );
}

function plugin_dir_url( $file ) {
    return 'http://example.com/wp-content/plugins/narrador-de-post/';
}

function wp_upload_dir() {
    return [
        'path' => sys_get_temp_dir() . '/wp-uploads/narrador-de-post',
        'url'  => 'http://example.com/wp-content/uploads/narrador-de-post',
        'basedir' => sys_get_temp_dir() . '/wp-uploads',
        'baseurl' => 'http://example.com/wp-content/uploads',
    ];
}

function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
    return true;
}

// Carrega o arquivo principal do plugin
require_once __DIR__ . '/../narrador-de-post.php';

// Suíte de Testes
class TestRunner {
    private static int $passed = 0;
    private static int $failed = 0;

    public static function assert( string $name, bool $condition, string $details = '' ) {
        if ( $condition ) {
            echo "  \033[32m✔ PASS:\033[0m {$name}\n";
            self::$passed++;
        } else {
            echo "  \033[31m✖ FAIL:\033[0m {$name}\n";
            if ( $details ) {
                echo "    \033[33mDetalhes:\033[0m {$details}\n";
            }
            self::$failed++;
        }
    }

    public static function summary() {
        echo "\n===============================\n";
        echo "Testes Concluídos: " . (self::$passed + self::$failed) . "\n";
        echo "\033[32mPassaram: " . self::$passed . "\033[0m | ";
        echo "\033[31mFalharam: " . self::$failed . "\033[0m\n";
        echo "===============================\n";
        return self::$failed === 0 ? 0 : 1;
    }
}

echo "Iniciando Suíte de Testes TDD: Narrador de Post (v2.0)\n\n";

if ( ! class_exists( 'Narrador_De_Post' ) ) {
    TestRunner::assert( 'Classe Narrador_De_Post deve existir', false, 'Classe não declarada' );
    exit( TestRunner::summary() );
}

$plugin = Narrador_De_Post::get_instance();

// Teste 1: Instância Singleton
TestRunner::assert(
    'Classe Narrador_De_Post deve ser instanciada como Singleton',
    $plugin instanceof Narrador_De_Post
);

// Teste 2: Extração e Limpeza de Texto para Narração
$html_content = '<p>Este é o primeiro parágrafo com um <a href="https://example.com">link</a>.</p>' .
                '<div class="social-share"><button>Compartilhar</button></div>' .
                '<script>console.log("ignore");</script>' .
                '<p>Segundo parágrafo [shortcode_ignorado] com <strong>formatação</strong>.</p>';

$clean_text = $plugin->extract_clean_text( $html_content );

TestRunner::assert(
    'Extrator deve limpar tags HTML, scripts, shortcodes e botões',
    strpos( $clean_text, 'Este é o primeiro parágrafo com um link.' ) !== false &&
    strpos( $clean_text, 'Compartilhar' ) === false &&
    strpos( $clean_text, 'console.log' ) === false &&
    strpos( $clean_text, 'shortcode_ignorado' ) === false &&
    strpos( $clean_text, 'Segundo parágrafo com formatação.' ) !== false,
    "Texto extraído: {$clean_text}"
);

// Teste 3: Renderização do Player no Modo Gratuito (Navegador / Web Speech)
$wp_options['narrador_settings'] = [
    'mode'               => 'browser',
    'button_label'       => 'Ouvir este artigo',
    'voice_lang'         => 'pt-BR',
    'default_rate'       => '1.0',
    'show_reading_time'  => '1',
    'auto_insert'        => 'top',
    'post_types'         => ['post'],
];

$player_html = $plugin->render_player( '<p>Olá mundo!</p>' );

TestRunner::assert(
    'Player no Modo Navegador deve conter botões de controle e atributos de voz',
    strpos( $player_html, 'class="narrador-player-container"' ) !== false &&
    strpos( $player_html, 'data-mode="browser"' ) !== false &&
    strpos( $player_html, 'data-lang="pt-BR"' ) !== false &&
    strpos( $player_html, 'narrador-btn-play' ) !== false &&
    strpos( $player_html, 'narrador-speed-select' ) !== false,
    "HTML gerado: {$player_html}"
);

// Teste 4: Renderização do Player no Modo IA (OpenAI / MP3 em Cache)
$wp_options['narrador_settings'] = [
    'mode'               => 'openai',
    'openai_api_key'     => 'sk-proj-testkey123',
    'openai_voice'       => 'alloy',
    'openai_model'       => 'tts-1',
    'button_label'       => 'Ouvir este artigo',
    'auto_insert'        => 'top',
    'post_types'         => ['post'],
];
// Simula meta de post com áudio gerado
$wp_post_meta[42]['_narrador_mp3_url'] = 'http://example.com/wp-content/uploads/narrador-de-post/post-42.mp3';

$player_ia_html = $plugin->render_player( '<p>Artigo narrado por IA.</p>' );

TestRunner::assert(
    'Player no Modo IA com áudio gerado deve incorporar a tag <audio> com URL do MP3',
    strpos( $player_ia_html, 'data-mode="audio_file"' ) !== false &&
    strpos( $player_ia_html, 'src="http://example.com/wp-content/uploads/narrador-de-post/post-42.mp3"' ) !== false &&
    strpos( $player_ia_html, '<audio' ) !== false,
    "HTML gerado: {$player_ia_html}"
);

// Teste 5: Shortcode [narrador_de_post]
$shortcode_output = do_shortcode( '[narrador_de_post]' );
TestRunner::assert(
    'Shortcode [narrador_de_post] deve renderizar o player corretamente',
    strpos( $shortcode_output, 'narrador-player-container' ) !== false
);

// Teste 6: Injeção Automática no Conteúdo (the_content)
$wp_options['narrador_settings']['auto_insert'] = 'top';
$original_body = '<p>Corpo principal da postagem.</p>';
$injected = $plugin->auto_insert_player( $original_body );

TestRunner::assert(
    'Injeção automática "top" deve inserir o player antes do conteúdo original',
    strpos( $injected, 'narrador-player-container' ) !== false &&
    strpos( $injected, $original_body ) > strpos( $injected, 'narrador-player-container' )
);

// Teste 7: Não deve inserir em Post Types não autorizados
$current_post_type = 'page';
$wp_options['narrador_settings']['post_types'] = ['post']; // apenas post
$result_cpt = $plugin->auto_insert_player( $original_body );

TestRunner::assert(
    'Não deve inserir o player em tipos de post não permitidos',
    $result_cpt === $original_body
);
$current_post_type = 'post';

// Teste 8: Não deve inserir em IDs excluídos
$wp_options['narrador_settings']['excluded_ids'] = '42, 99';
$current_post_id = 42;
$result_excluded = $plugin->auto_insert_player( $original_body );

TestRunner::assert(
    'Não deve inserir o player quando o post_id está na lista de exclusão',
    $result_excluded === $original_body
);
$wp_options['narrador_settings']['excluded_ids'] = '';

// Teste 9: Sanitização Segura de Configurações
$raw_settings = [
    'mode'               => 'openai',
    'openai_api_key'     => '  sk-proj-1234567890abcdef  ',
    'elevenlabs_api_key' => '  el-api-key-test  ',
    'openai_voice'       => 'nova',
    'button_label'       => ' <b>Ouvir Artigo</b> ',
    'auto_insert'        => 'top',
    'post_types'         => ['post', 'custom_cpt', 'bad_cpt$#'],
    'excluded_ids'       => ' 12, 45, abc, 99 ',
    'custom_css'         => 'margin: 10px; <script>',
];

$sanitized = $plugin->sanitize_settings( $raw_settings );

TestRunner::assert(
    'Sanitização deve aparar chaves de API sem alterar caracteres válidos',
    $sanitized['openai_api_key'] === 'sk-proj-1234567890abcdef' &&
    $sanitized['elevenlabs_api_key'] === 'el-api-key-test'
);

TestRunner::assert(
    'Sanitização deve limpar tags HTML perigosas dos rótulos e IDs',
    $sanitized['button_label'] === 'Ouvir Artigo' &&
    $sanitized['excluded_ids'] === '12, 45, 99' &&
    $sanitized['post_types'] === ['post', 'custom_cpt', 'bad_cpt']
);

// Teste 10: Estimativa de Tempo de Leitura
$long_text = str_repeat( 'Palavra de exemplo para teste de tempo de leitura. ', 150 ); // ~1200 palavras
$minutes = $plugin->calculate_reading_time( $long_text );

TestRunner::assert(
    'Cálculo de tempo de leitura deve retornar estimativa precisa em minutos',
    $minutes >= 6 && $minutes <= 10,
    "Minutos calculados: {$minutes}"
);

exit( TestRunner::summary() );
