<?php
/**
 * Plugin Name:       Narrador de Post
 * Plugin URI:        https://github.com/mailsonm/narrador-de-post
 * Description:       Leitor de áudio inteligente e conversor de texto em fala (TTS) híbrido para WordPress. Suporta vozes neurais do navegador (100% gratuito) e IA Neural de alta fidelidade (OpenAI TTS / ElevenLabs).
 * Version:           2.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Mailson Maia Alves
 * Author URI:        https://github.com/mailsonm
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       narrador-de-post
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe Principal do Narrador de Post
 */
class Narrador_De_Post {

    public const VERSION = '2.0.0';
    public const SLUG = 'narrador-de-post';
    public const OPTION_NAME = 'narrador_settings';
    public const SETTINGS_GROUP = 'narrador_settings_group';

    private static ?Narrador_De_Post $instance = null;
    private bool $is_processing = false;

    public static function get_instance(): Narrador_De_Post {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct() {
        $this->register_hooks();
    }

    public function register_hooks(): void {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'settings_init' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
        add_filter( 'the_content', [ $this, 'auto_insert_player' ], 15 );
        add_shortcode( 'narrador_de_post', [ $this, 'shortcode_narrador' ] );
        add_shortcode( 'narrador_post', [ $this, 'shortcode_narrador' ] );
        add_action( 'wp_ajax_narrador_generate_audio', [ $this, 'ajax_generate_audio' ] );
        add_action( 'save_post', [ $this, 'on_save_post' ], 10, 2 );
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            self::SLUG,
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    public function add_admin_menu(): void {
        add_menu_page(
            __( 'Narrador de Post', 'narrador-de-post' ),
            __( 'Narrador de Post', 'narrador-de-post' ),
            'manage_options',
            self::SLUG,
            [ $this, 'render_admin_page' ],
            'dashicons-controls-volumeon',
            105
        );
    }

    public function enqueue_frontend_assets(): void {
        if ( is_singular() ) {
            wp_enqueue_style(
                self::SLUG . '-style',
                plugin_dir_url( __FILE__ ) . 'narrador-de-post.css',
                [],
                self::VERSION
            );

            wp_enqueue_script(
                self::SLUG . '-script',
                plugin_dir_url( __FILE__ ) . 'narrador-de-post.js',
                [],
                self::VERSION,
                true
            );

            $options = get_option( self::OPTION_NAME, [] );
            wp_localize_script(
                self::SLUG . '-script',
                'NarradorConfig',
                [
                    'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                    'defaultLang'   => $options['voice_lang'] ?? 'pt-BR',
                    'defaultRate'   => floatval( $options['default_rate'] ?? 1.0 ),
                    'btnListen'     => $options['button_label'] ?? __( 'Ouvir este artigo', 'narrador-de-post' ),
                    'btnPause'      => __( 'Pausar', 'narrador-de-post' ),
                    'btnResume'     => __( 'Continuar', 'narrador-de-post' ),
                    'btnStop'       => __( 'Parar', 'narrador-de-post' ),
                    'readingLabel'  => __( 'minutos de áudio', 'narrador-de-post' ),
                    'i18nNoSupport' => __( 'Seu navegador não possui suporte à síntese de voz.', 'narrador-de-post' ),
                ]
            );
        }
    }

    public function settings_init(): void {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            [ $this, 'sanitize_settings' ]
        );
    }

    /**
     * Sanitiza configurações
     */
    public function sanitize_settings( $input ): array {
        $sanitized = [];

        // Modo de Operação (browser | openai | elevenlabs)
        $valid_modes = [ 'browser', 'openai', 'elevenlabs' ];
        $sanitized['mode'] = in_array( $input['mode'] ?? 'browser', $valid_modes, true )
            ? sanitize_key( $input['mode'] )
            : 'browser';

        // Rótulo do Botão
        $sanitized['button_label'] = sanitize_text_field( $input['button_label'] ?? __( 'Ouvir este artigo', 'narrador-de-post' ) );
        if ( empty( $sanitized['button_label'] ) ) {
            $sanitized['button_label'] = __( 'Ouvir este artigo', 'narrador-de-post' );
        }

        // Posição de Inserção Automática (top | bottom | manual)
        $valid_positions = [ 'top', 'bottom', 'manual' ];
        $sanitized['auto_insert'] = in_array( $input['auto_insert'] ?? 'top', $valid_positions, true )
            ? sanitize_key( $input['auto_insert'] )
            : 'top';

        // Idioma e Velocidade Padrão
        $sanitized['voice_lang'] = sanitize_text_field( $input['voice_lang'] ?? 'pt-BR' );
        $rate = (string) ( $input['default_rate'] ?? '1.0' );
        $sanitized['default_rate'] = in_array( $rate, [ '0.75', '1.0', '1.25', '1.5', '2.0' ], true )
            ? $rate
            : '1.0';

        $sanitized['show_reading_time'] = ! empty( $input['show_reading_time'] ) ? '1' : '0';

        // Post Types Permitidos
        if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
            $sanitized['post_types'] = array_values( array_map( 'sanitize_key', $input['post_types'] ) );
        } else {
            $sanitized['post_types'] = [ 'post' ];
        }

        // IDs Excluídos
        if ( ! empty( $input['excluded_ids'] ) ) {
            $ids = array_map( 'absint', explode( ',', (string) $input['excluded_ids'] ) );
            $ids = array_filter( $ids, static fn( $id ) => $id > 0 );
            $sanitized['excluded_ids'] = implode( ', ', array_unique( $ids ) );
        } else {
            $sanitized['excluded_ids'] = '';
        }

        // Configurações de API OpenAI
        $sanitized['openai_api_key'] = sanitize_text_field( trim( (string) ( $input['openai_api_key'] ?? '' ) ) );
        $model = (string) ( $input['openai_model'] ?? 'tts-1' );
        $sanitized['openai_model']   = in_array( $model, [ 'tts-1', 'tts-1-hd' ], true )
            ? $model
            : 'tts-1';
        $valid_openai_voices = [ 'alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer' ];
        $voice = (string) ( $input['openai_voice'] ?? 'nova' );
        $sanitized['openai_voice'] = in_array( $voice, $valid_openai_voices, true )
            ? sanitize_key( $voice )
            : 'nova';

        // Configurações de API ElevenLabs
        $sanitized['elevenlabs_api_key']  = sanitize_text_field( trim( (string) ( $input['elevenlabs_api_key'] ?? '' ) ) );
        $sanitized['elevenlabs_voice_id'] = sanitize_text_field( trim( (string) ( $input['elevenlabs_voice_id'] ?? '' ) ) );

        // Estilos Customizados
        $theme = (string) ( $input['player_theme'] ?? 'modern' );
        $sanitized['player_theme'] = in_array( $theme, [ 'modern', 'minimal', 'compact' ], true )
            ? sanitize_key( $theme )
            : 'modern';

        return $sanitized;
    }

    /**
     * Extrai e higieniza o texto do post para ser lido
     */
    public function extract_clean_text( string $html ): string {
        // Remove scripts, estilos, iframes, botões e formulários
        $clean = preg_replace( '@<(script|style|iframe|button|form|nav|footer|header)[^>]*?>.*?</\\1>@si', '', $html );
        // Remove shortcodes não renderizados
        if ( function_exists( 'strip_shortcodes' ) ) {
            $clean = strip_shortcodes( $clean );
        }
        $clean = preg_replace( '/\[.*?\]/', '', $clean );
        // Insere espaço entre tags de bloco para não colar palavras
        $clean = preg_replace( '/<(p|div|br|li|h[1-6]|tr|td|th|article|section)[^>]*>/i', ' $0', $clean );
        $clean = preg_replace( '/<\/(p|div|li|h[1-6]|tr|td|th|article|section)>/i', '$0 ', $clean );
        // Remove tags HTML
        if ( function_exists( 'wp_strip_all_tags' ) ) {
            $clean = wp_strip_all_tags( $clean, true );
        } else {
            $clean = strip_tags( $clean );
        }
        // Normaliza espaços em branco e pontuação
        $clean = preg_replace( '/\s+/', ' ', $clean );
        return trim( $clean );
    }

    /**
     * Calcula estimativa de tempo de áudio (base: ~140 palavras por minuto)
     */
    public function calculate_reading_time( string $text ): int {
        $word_count = count( preg_split( '/\s+/', trim( $text ) ) );
        return max( 1, (int) ceil( $word_count / 140 ) );
    }

    /**
     * Inserção automática do player no hook the_content
     */
    public function auto_insert_player( string $content ): string {
        if ( $this->is_processing ) {
            return $content;
        }

        $options            = get_option( self::OPTION_NAME, [] );
        $allowed_post_types = $options['post_types'] ?? [ 'post' ];
        $auto_insert        = $options['auto_insert'] ?? 'top';

        if ( 'manual' === $auto_insert || ! is_singular( $allowed_post_types ) || is_feed() ) {
            return $content;
        }

        $post_id = get_the_ID();
        if ( ! empty( $options['excluded_ids'] ) && $post_id ) {
            $excluded = array_map( 'absint', explode( ',', (string) $options['excluded_ids'] ) );
            if ( in_array( (int) $post_id, $excluded, true ) ) {
                return $content;
            }
        }

        $this->is_processing = true;
        $player_html = $this->render_player( $content );
        $this->is_processing = false;

        if ( 'bottom' === $auto_insert ) {
            return $content . "\n" . $player_html;
        }

        return $player_html . "\n" . $content;
    }

    /**
     * Callback do Shortcode [narrador_de_post]
     */
    public function shortcode_narrador( $atts = [] ): string {
        global $post;
        $content = isset( $post->post_content ) ? $post->post_content : '';
        return $this->render_player( $content );
    }

    /**
     * Renderiza o HTML do Player
     */
    public function render_player( string $content = '' ): string {
        $options   = get_option( self::OPTION_NAME, [] );
        $post_id   = get_the_ID();
        $mode      = $options['mode'] ?? 'browser';
        $btn_label = ! empty( $options['button_label'] ) ? $options['button_label'] : __( 'Ouvir este artigo', 'narrador-de-post' );
        $lang      = $options['voice_lang'] ?? 'pt-BR';
        $rate      = $options['default_rate'] ?? '1.0';
        $show_time = ( $options['show_reading_time'] ?? '1' ) === '1';

        $clean_text   = $this->extract_clean_text( $content );
        $reading_time = $this->calculate_reading_time( $clean_text );

        // Verifica se há MP3 em cache gerado por IA para este post
        $mp3_url = $post_id ? get_post_meta( $post_id, '_narrador_mp3_url', true ) : '';
        $is_mp3_ready = ! empty( $mp3_url );

        $actual_mode = ( 'browser' !== $mode && $is_mp3_ready ) ? 'audio_file' : 'browser';

        ob_start();
        ?>
        <div class="narrador-player-container" 
             id="narrador-player-<?php echo esc_attr( $post_id ?: '0' ); ?>"
             data-mode="<?php echo esc_attr( $actual_mode ); ?>"
             data-lang="<?php echo esc_attr( $lang ); ?>"
             data-rate="<?php echo esc_attr( $rate ); ?>"
             data-post-id="<?php echo esc_attr( $post_id ?: '0' ); ?>">

            <div class="narrador-player-card">
                <div class="narrador-header-row">
                    <button type="button" class="narrador-btn-play" aria-label="<?php echo esc_attr( $btn_label ); ?>">
                        <span class="narrador-icon-play">🔊</span>
                        <span class="narrador-btn-text"><?php echo esc_html( $btn_label ); ?></span>
                    </button>

                    <?php if ( $show_time ) : ?>
                        <div class="narrador-meta-info">
                            <span class="narrador-time-badge">⏱️ ~<?php echo esc_html( $reading_time ); ?> min</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="narrador-controls-panel" style="display: none;">
                    <div class="narrador-actions-row">
                        <button type="button" class="narrador-btn-pause" title="<?php esc_attr_e( 'Pausar / Retomar', 'narrador-de-post' ); ?>">
                            <span class="narrador-icon-pause">⏸️</span>
                        </button>

                        <button type="button" class="narrador-btn-stop" title="<?php esc_attr_e( 'Parar Narração', 'narrador-de-post' ); ?>">
                            <span class="narrador-icon-stop">⏹️</span>
                        </button>

                        <div class="narrador-speed-wrapper">
                            <label for="narrador-speed-<?php echo esc_attr( $post_id ?: '0' ); ?>"><?php esc_html_e( 'Velocidade:', 'narrador-de-post' ); ?></label>
                            <select class="narrador-speed-select" id="narrador-speed-<?php echo esc_attr( $post_id ?: '0' ); ?>">
                                <option value="0.75" <?php selected( $rate, '0.75' ); ?>>0.75x</option>
                                <option value="1.0" <?php selected( $rate, '1.0' ); ?>>1.0x</option>
                                <option value="1.25" <?php selected( $rate, '1.25' ); ?>>1.25x</option>
                                <option value="1.5" <?php selected( $rate, '1.5' ); ?>>1.5x</option>
                                <option value="2.0" <?php selected( $rate, '2.0' ); ?>>2.0x</option>
                            </select>
                        </div>
                    </div>

                    <div class="narrador-progress-bar-wrapper">
                        <div class="narrador-progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="narrador-progress-fill" style="width: 0%;"></div>
                        </div>
                        <span class="narrador-progress-text">0%</span>
                    </div>
                </div>

                <?php if ( ! empty( $mp3_url ) ) : ?>
                    <audio class="narrador-audio-element" src="<?php echo esc_url( $mp3_url ); ?>" preload="none"></audio>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return trim( (string) ob_get_clean() );
    }

    /**
     * Geração de Áudio IA ao Salvar Post
     */
    public function on_save_post( int $post_id, $post ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $options = get_option( self::OPTION_NAME, [] );
        if ( ( $options['mode'] ?? 'browser' ) === 'openai' && ! empty( $options['openai_api_key'] ) ) {
            // Sintetiza áudio via OpenAI em background se post publicado
            if ( $post && 'publish' === $post->post_status ) {
                $this->generate_openai_audio( $post_id, $post->post_content );
            }
        }
    }

    /**
     * Sintetiza áudio via OpenAI TTS
     */
    public function generate_openai_audio( int $post_id, string $content ): bool {
        $options = get_option( self::OPTION_NAME, [] );
        $api_key = $options['openai_api_key'] ?? '';
        if ( empty( $api_key ) ) return false;

        $text = $this->extract_clean_text( $content );
        if ( empty( $text ) ) return false;

        // Limita a 4096 caracteres por chunk (limite OpenAI TTS)
        $text_chunk = mb_substr( $text, 0, 4000 );

        $voice = $options['openai_voice'] ?? 'nova';
        $model = $options['openai_model'] ?? 'tts-1';

        $response = wp_remote_post( 'https://api.openai.com/v1/audio/speech', [
            'timeout' => 45,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode( [
                'model' => $model,
                'input' => $text_chunk,
                'voice' => $voice,
            ] ),
        ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $audio_data = wp_remote_retrieve_body( $response );
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/narrador-de-post';

        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }

        $file_name = 'post-' . $post_id . '-' . md5( $text_chunk ) . '.mp3';
        $file_path = $target_dir . '/' . $file_name;
        $file_url  = $upload_dir['baseurl'] . '/narrador-de-post/' . $file_name;

        file_put_contents( $file_path, $audio_data );
        update_post_meta( $post_id, '_narrador_mp3_url', $file_url );

        return true;
    }

    /**
     * AJAX endpoint para gerar áudio sob demanda no painel
     */
    public function ajax_generate_audio(): void {
        check_ajax_referer( 'narrador_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permissão negada.', 'narrador-de-post' ) ] );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $post    = get_post( $post_id );

        if ( ! $post ) {
            wp_send_json_error( [ 'message' => __( 'Post não encontrado.', 'narrador-de-post' ) ] );
        }

        $success = $this->generate_openai_audio( $post_id, $post->post_content );
        if ( $success ) {
            $url = get_post_meta( $post_id, '_narrador_mp3_url', true );
            wp_send_json_success( [ 'url' => $url ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Falha ao sintetizar áudio. Verifique sua chave de API.', 'narrador-de-post' ) ] );
        }
    }

    /**
     * Renderiza o Painel de Administração do Plugin
     */
    public function render_admin_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'narrador-de-post' ) );
        }

        $options = get_option( self::OPTION_NAME, [] );
        $mode    = $options['mode'] ?? 'browser';
        ?>
        <div class="wrap narrador-admin-wrap">
            <h1 style="display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-controls-volumeon" style="font-size: 30px; width: 30px; height: 30px;"></span>
                <?php echo esc_html( get_admin_page_title() ); ?>
                <span style="font-size: 13px; font-weight: normal; background: #0073aa; color: #fff; padding: 2px 8px; border-radius: 12px;">v<?php echo esc_html( self::VERSION ); ?> (Híbrido)</span>
            </h1>

            <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
                <!-- Formulário de Configurações -->
                <div style="flex: 1 1 650px; min-width: 320px; background: #fff; padding: 25px; border: 1px solid #ccd0d4; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <form action="options.php" method="post">
                        <?php settings_fields( self::SETTINGS_GROUP ); ?>

                        <h2 style="border-bottom: 2px solid #2271b1; padding-bottom: 8px; margin-top: 0;"><?php esc_html_e( '1. Modo de Operação do Player', 'narrador-de-post' ); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Selecione a Tecnologia:', 'narrador-de-post' ); ?></th>
                                <td>
                                    <fieldset>
                                        <label style="display: block; margin-bottom: 10px;">
                                            <input type="radio" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[mode]" value="browser" <?php checked( $mode, 'browser' ); ?>>
                                            <strong><?php esc_html_e( 'Modo Gratuito (Vozes Neurais do Navegador)', 'narrador-de-post' ); ?></strong>
                                            <p class="description" style="margin-left: 24px;"><?php esc_html_e( '100% gratuito e sem custos de API. Utiliza sintetizadores neurais de alta qualidade nativos do dispositivo do usuário (Edge/Chrome/Safari).', 'narrador-de-post' ); ?></p>
                                        </label>
                                        <label style="display: block;">
                                            <input type="radio" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[mode]" value="openai" <?php checked( $mode, 'openai' ); ?>>
                                            <strong><?php esc_html_e( 'Modo IA de Alta Fidelidade (OpenAI TTS - BYOK)', 'narrador-de-post' ); ?></strong>
                                            <p class="description" style="margin-left: 24px;"><?php esc_html_e( 'Áudio com qualidade de podcast profissional. O áudio é sintetizado via sua chave OpenAI e salvo em MP3 no WordPress.', 'narrador-de-post' ); ?></p>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>

                        <h2 style="border-bottom: 2px solid #2271b1; padding-bottom: 8px; margin-top: 20px;"><?php esc_html_e( '2. Configurações de Voz & Áudio', 'narrador-de-post' ); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="button_label"><?php esc_html_e( 'Texto do Botão:', 'narrador-de-post' ); ?></label></th>
                                <td>
                                    <input type="text" id="button_label" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[button_label]" value="<?php echo esc_attr( $options['button_label'] ?? __( 'Ouvir este artigo', 'narrador-de-post' ) ); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="voice_lang"><?php esc_html_e( 'Idioma Principal:', 'narrador-de-post' ); ?></label></th>
                                <td>
                                    <select id="voice_lang" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[voice_lang]">
                                        <option value="pt-BR" <?php selected( $options['voice_lang'] ?? 'pt-BR', 'pt-BR' ); ?>>Português (Brasil) - pt-BR</option>
                                        <option value="pt-PT" <?php selected( $options['voice_lang'] ?? '', 'pt-PT' ); ?>>Português (Portugal) - pt-PT</option>
                                        <option value="en-US" <?php selected( $options['voice_lang'] ?? '', 'en-US' ); ?>>Inglês (EUA) - en-US</option>
                                        <option value="es-ES" <?php selected( $options['voice_lang'] ?? '', 'es-ES' ); ?>>Espanhol - es-ES</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="default_rate"><?php esc_html_e( 'Velocidade Padrão:', 'narrador-de-post' ); ?></label></th>
                                <td>
                                    <select id="default_rate" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_rate]">
                                        <option value="0.75" <?php selected( $options['default_rate'] ?? '1.0', '0.75' ); ?>>0.75x</option>
                                        <option value="1.0" <?php selected( $options['default_rate'] ?? '1.0', '1.0' ); ?>>1.0x (Normal)</option>
                                        <option value="1.25" <?php selected( $options['default_rate'] ?? '1.0', '1.25' ); ?>>1.25x</option>
                                        <option value="1.5" <?php selected( $options['default_rate'] ?? '1.0', '1.5' ); ?>>1.5x</option>
                                        <option value="2.0" <?php selected( $options['default_rate'] ?? '1.0', '2.0' ); ?>>2.0x</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Tempo Estimado:', 'narrador-de-post' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[show_reading_time]" value="1" <?php checked( $options['show_reading_time'] ?? '1', '1' ); ?>>
                                        <?php esc_html_e( 'Exibir badge com tempo estimado de áudio (ex: ⏱️ ~3 min)', 'narrador-de-post' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <h2 style="border-bottom: 2px solid #2271b1; padding-bottom: 8px; margin-top: 20px;"><?php esc_html_e( '3. Chave de API OpenAI (Opcional para Modo IA)', 'narrador-de-post' ); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="openai_api_key"><?php esc_html_e( 'OpenAI API Key:', 'narrador-de-post' ); ?></label></th>
                                <td>
                                    <input type="password" id="openai_api_key" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[openai_api_key]" value="<?php echo esc_attr( $options['openai_api_key'] ?? '' ); ?>" class="regular-text" placeholder="sk-proj-...">
                                    <p class="description"><?php esc_html_e( 'Sua chave privada da OpenAI. O áudio gerado fica armazenado no seu WordPress e não consome API em novas visitas.', 'narrador-de-post' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="openai_voice"><?php esc_html_e( 'Voz da OpenAI:', 'narrador-de-post' ); ?></label></th>
                                <td>
                                    <select id="openai_voice" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[openai_voice]">
                                        <option value="nova" <?php selected( $options['openai_voice'] ?? 'nova', 'nova' ); ?>>Nova (Feminina Natural - Recomendada)</option>
                                        <option value="shimmer" <?php selected( $options['openai_voice'] ?? '', 'shimmer' ); ?>>Shimmer (Feminina Clara)</option>
                                        <option value="alloy" <?php selected( $options['openai_voice'] ?? '', 'alloy' ); ?>>Alloy (Neutra Equilibrada)</option>
                                        <option value="echo" <?php selected( $options['openai_voice'] ?? '', 'echo' ); ?>>Echo (Masculina Suave)</option>
                                        <option value="onyx" <?php selected( $options['openai_voice'] ?? '', 'onyx' ); ?>>Onyx (Masculina Profunda)</option>
                                        <option value="fable" <?php selected( $options['openai_voice'] ?? '', 'fable' ); ?>>Fable (Narrativa Expressiva)</option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <h2 style="border-bottom: 2px solid #2271b1; padding-bottom: 8px; margin-top: 20px;"><?php esc_html_e( '4. Exibição e Tipos de Postagem', 'narrador-de-post' ); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Posicionamento:', 'narrador-de-post' ); ?></th>
                                <td>
                                    <select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[auto_insert]">
                                        <option value="top" <?php selected( $options['auto_insert'] ?? 'top', 'top' ); ?>><?php esc_html_e( 'Topo do Post (Início do Conteúdo - Recomendado)', 'narrador-de-post' ); ?></option>
                                        <option value="bottom" <?php selected( $options['auto_insert'] ?? '', 'bottom' ); ?>><?php esc_html_e( 'Final do Post (Rodapé do Conteúdo)', 'narrador-de-post' ); ?></option>
                                        <option value="manual" <?php selected( $options['auto_insert'] ?? '', 'manual' ); ?>><?php esc_html_e( 'Manual (Apenas via Shortcode [narrador_de_post])', 'narrador-de-post' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Tipos de Post:', 'narrador-de-post' ); ?></th>
                                <td>
                                    <fieldset>
                                        <?php
                                        $selected_types = $options['post_types'] ?? [ 'post' ];
                                        $post_types = get_post_types( [ 'public' => true ], 'objects' );
                                        foreach ( $post_types as $pt ) :
                                            if ( 'attachment' === $pt->name ) continue;
                                            $checked = in_array( $pt->name, (array) $selected_types, true ) ? 'checked="checked"' : '';
                                        ?>
                                            <label style="margin-right: 15px; display: inline-block; margin-bottom: 6px;">
                                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php echo $checked; ?>>
                                                <?php echo esc_html( $pt->labels->singular_name ?? $pt->name ); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="excluded_ids"><?php esc_html_e( 'Excluir Posts por ID:', 'narrador-de-post' ); ?></label></th>
                                <td>
                                    <input type="text" id="excluded_ids" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[excluded_ids]" value="<?php echo esc_attr( $options['excluded_ids'] ?? '' ); ?>" class="regular-text" placeholder="10, 42, 88">
                                    <p class="description"><?php esc_html_e( 'IDs de post separados por vírgula que não devem exibir o narrador.', 'narrador-de-post' ); ?></p>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button( __( 'Salvar Configurações', 'narrador-de-post' ) ); ?>
                    </form>
                </div>

                <!-- Painel Lateral / Manual de APIs -->
                <div style="flex: 0 1 360px; min-width: 280px;">
                    <div style="background: #e7f3fe; border-left: 4px solid #0073aa; padding: 18px; border-radius: 4px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-book"></span>
                            <?php esc_html_e( 'Guia de APIs & Custo-Benefício', 'narrador-de-post' ); ?>
                        </h3>
                        <p style="font-size: 13px; line-height: 1.5;">
                            <?php esc_html_e( 'Escolha a melhor opção de voz e tecnologia para o seu projeto:', 'narrador-de-post' ); ?>
                        </p>
                        
                        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 10px; margin-bottom: 12px;">
                            <h4 style="margin: 0 0 4px 0; font-size: 13px; color: #135e96;">🥇 Modo Gratuito (Navegador)</h4>
                            <p style="font-size: 12px; margin: 0; color: #50575e;">
                                <strong>Custo: R$ 0,00</strong>. Utiliza as vozes neurais instaladas no dispositivo do visitante. Sem necessidade de chaves de API.
                            </p>
                        </div>

                        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 10px; margin-bottom: 12px;">
                            <h4 style="margin: 0 0 4px 0; font-size: 13px; color: #135e96;">🔹 OpenAI TTS (Recomendado para IA)</h4>
                            <p style="font-size: 12px; margin: 0 0 6px 0; color: #50575e;">
                                <strong>Custo: ~$0,015 / 1k caracteres</strong> (~$0,05 por post médio). Qualidade de estúdio/podcast.
                            </p>
                            <ol style="font-size: 11px; line-height: 1.5; padding-left: 16px; margin: 0;">
                                <li>Acesse <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">platform.openai.com/api-keys</a>.</li>
                                <li>Crie uma chave privada (<code>sk-proj-...</code>) e cole ao lado.</li>
                            </ol>
                        </div>

                        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 10px; margin-bottom: 12px;">
                            <h4 style="margin: 0 0 4px 0; font-size: 13px; color: #135e96;">🎁 Outras APIs com Cotas Gratuitas:</h4>
                            <ul style="font-size: 11px; line-height: 1.5; padding-left: 16px; margin: 0;">
                                <li><strong>Google Cloud TTS:</strong> 1 milhão de caracteres/mês grátis (<a href="https://cloud.google.com/text-to-speech" target="_blank" rel="noopener noreferrer">Acessar</a>).</li>
                                <li><strong>Microsoft Azure Speech:</strong> 500 mil caracteres/mês grátis (<a href="https://azure.microsoft.com/products/ai-services/ai-speech" target="_blank" rel="noopener noreferrer">Acessar</a>).</li>
                                <li><strong>ElevenLabs:</strong> 10 mil caracteres/mês grátis (<a href="https://elevenlabs.io" target="_blank" rel="noopener noreferrer">Acessar</a>).</li>
                            </ul>
                        </div>

                        <div style="background: #f0f6fc; padding: 8px 10px; border-radius: 4px; font-size: 11px; color: #24292f;">
                            💡 <strong>Cache Inteligente:</strong> O áudio MP3 é salvo no WordPress e a API só é chamada <strong>1 única vez</strong> por postagem!
                        </div>
                    </div>

                    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 18px; border-radius: 4px;">
                        <h3 style="margin-top: 0; font-size: 14px;">🧩 Shortcode & Integração</h3>
                        <p style="font-size: 13px;">Você pode inserir o narrador em qualquer lugar do seu conteúdo com:</p>
                        <code style="display: block; padding: 8px; background: #f0f0f1; border-radius: 4px; font-size: 13px;">[narrador_de_post]</code>
                        <p style="font-size: 12px; color: #646970; margin-top: 10px;">
                            💡 Dica: Você pode usar o plugin <strong>Auto Shortcode Inserter</strong> para injetar esse shortcode automaticamente no meio ou topo de posts.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

Narrador_De_Post::get_instance();
