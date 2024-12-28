<?php
/**
 * Plugin Name: Narrador-de-Post
 * Plugin URI:  https://github.com/mailsonm/narrador-de-post
 * Description: Widget de conversão de texto em fala para posts do WordPress usando o Read Aloud Widget.
 * Version:     1.1.0
 * Author:      mailsonm
 * Author URI:  https://mailsondev.com.br/
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: narrador-de-post
 * Domain Path: /languages
 */

// Função para adicionar o widget ao conteúdo de posts
function narrador_widget($content) {
    // Verifica se o conteúdo é de um post singular
    if (is_singular('post')) {
        // Obtém as configurações salvas do plugin
        $options = get_option('narrador_settings');
        // Se o modo estiver configurado como "auto", adiciona o código do widget antes do conteúdo do post
        if ($options['mode'] == 'auto') return $options['code'] . $content;
    }
    // Retorna o conteúdo original caso nenhuma alteração seja feita
    return $content;
}

// Função executada ao ativar o plugin
function narrador_activate() {
    // Cria uma configuração padrão ao ativar o plugin
    add_option('narrador_settings', array(
        'mode' => 'auto', // Modo padrão: "auto"
        'api_key' => 'demo', // Chave de API de demonstração
        'lang' => 'pt-BR', // Idioma padrão
        'voice' => 'free', // Voz padrão
        'code' => getEmbedCode('demo', 'pt-BR', 'free') // Código incorporado do widget
    ));
}

// Adiciona a opção do plugin ao menu de configurações no painel administrativo
function narrador_menu() {
    add_options_page(
        'SiteNarrator Widget',
        'SiteNarrator Widget',
        'manage_options',
        'narrador_settings_page',
        'narrador_settings'
    );
}

// Exibe a página de configurações do plugin no painel administrativo
function narrador_settings() {
?>
<div class="wrap">
    <h2>SiteNarrator Widget</h2>
    <form action="options.php" method="post">
        <?php settings_fields('narrador_settings'); // Registra as configurações ?>
        <?php do_settings_sections('narrador_settings_page'); // Renderiza as seções e campos ?>
        <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
    </form>
</div>
<?php
}

// Registra as configurações, seções e campos para a página de configurações do plugin
function narrador_admin() {
    register_setting('narrador_settings', 'narrador_settings', 'narrador_settings_validate');

    // Seção de status
    add_settings_section(
        'narrador_status_section',
        'Status',
        'narrador_status_section_text',
        'narrador_settings_page'
    );

    // Campos de configuração
    add_settings_field(
        'narrador_mode',
        'Mode',
        'narrador_settings_mode',
        'narrador_settings_page',
        'narrador_status_section'
    );

    // Seção principal de configuração do widget
    add_settings_section(
        'narrador_main_settings',
        'Configure Widget',
        'narrador_main_settings_section_text',
        'narrador_settings_page'
    );

    // Campos específicos do widget
    add_settings_field('narrador_key', 'API key', 'narrador_settings_key', 'narrador_settings_page', 'narrador_main_settings');
    add_settings_field('narrador_lang', 'Language', 'narrador_settings_lang', 'narrador_settings_page', 'narrador_main_settings');
    add_settings_field('narrador_voice', 'Voice', 'narrador_settings_voice', 'narrador_settings_page', 'narrador_main_settings');
    add_settings_field('narrador_code', 'Widget Code', 'narrador_settings_code', 'narrador_settings_page', 'narrador_main_settings');
}

// Texto de explicação na seção de status
function narrador_status_section_text() {
?>
<p style="max-width: 60em; border: 1px dashed lightgray; padding: 1em;">
    Selecione como você deseja incorporar o widget. Lembre-se de clicar em "Salvar" para aplicar as alterações.
</p>
<?php
}

// Campo para seleção do modo de funcionamento (Desabilitado, Automático ou Manual)
function narrador_settings_mode() {
    $options = get_option('narrador_settings');
    $mode = isset($options['mode']) ? $options['mode'] : 'off';
?>
<p>
    <label><input type='radio' name='narrador_settings[mode]' value='off' <?php checked($mode == 'off') ?>> Desabilitado</label>
</p>
<p>
    <label><input type='radio' name='narrador_settings[mode]' value='auto' <?php checked($mode == 'auto') ?>> Adicionar automaticamente aos posts</label>
</p>
<p>
    <label><input type='radio' name='narrador_settings[mode]' value='manual' <?php checked($mode == 'manual') ?>> Inserir manualmente (usando o bloco SiteNarrator ou código incorporado)</label>
</p>
<?php
}

// Texto explicativo na seção de configurações principais
function narrador_main_settings_section_text() {
?>
<p style="max-width: 60em; border: 1px dashed lightgray; padding: 1em;">
    A chave de API "demo" é apenas para uso de demonstração.
    Cadastre-se para obter uma chave de API personalizada.
</p>
<?php
}

// Campo para configuração da chave de API
function narrador_settings_key() {
    $options = get_option('narrador_settings');
    $api_key = isset($options['api_key']) ? $options['api_key'] : 'demo';
    echo "<input id='narrador_key' name='narrador_settings[api_key]' size='32' type='text' value='{$api_key}' />";
}

// Funções similares configuram o idioma, voz e código do widget...

// Função de validação das configurações
function narrador_settings_validate($input) {
    $mode = trim($input['mode']);
    $key = trim($input['api_key']);
    $lang = trim($input['lang']);
    $voice = trim($input['voice']);
    $code = trim($input['code']);

    // Validações básicas para os valores fornecidos
    if (!preg_match('/^\w+$/', $key)) $key = '';
    if (!preg_match('/^[\w-]+$/', $lang)) $lang = '';
    if (!preg_match('/^[\w -]+$/', $voice)) $voice = '';

    return array(
        'mode' => $mode,
        'api_key' => $key,
        'lang' => $lang,
        'voice' => $voice,
        'code' => $code,
    );
}

// Ganchos para ativação do plugin, filtros e ações administrativas
register_activation_hook(__FILE__, 'narrador_activate');
add_filter('the_content', 'narrador_widget');
add_action('admin_menu', 'narrador_menu');
add_action('admin_init', 'narrador_admin');
add_action('admin_enqueue_scripts', 'narrador_admin_scripts');
add_action('init', 'narrador_register_block');

// Função para registrar os scripts administrativos
function narrador_admin_scripts($page) {
    if ($page == 'settings_page_narrador_settings_page') {
        wp_enqueue_script('narrador-de-post', plugin_dir_url(__FILE__) . 'narrador-de-post.js', array('jquery'), '1.4.6', true);
    }
}

// Função para registrar o bloco do editor Gutenberg
function narrador_register_block() {
    register_block_type(__DIR__ . '/block');
}

// Função para obter o código incorporado do widget
function getEmbedCode($key, $lang, $voice) {
    $response = wp_remote_get('https://assets.readaloudwidget.com/embed/code.html');
    if (is_wp_error($response)) {
        return NULL; // Retorna NULL se houver erro na requisição
    }
    else {
        $body = wp_remote_retrieve_body($response);
        $body = str_replace('${key}', $key, $body);
        $body = str_replace('${lang}', $lang, $body);
        $body = str_replace('${voice}', $voice, $body);
        return $body; // Retorna o código HTML incorporado
    }
}
