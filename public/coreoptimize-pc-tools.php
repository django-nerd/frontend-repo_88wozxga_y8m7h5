<?php
/**
 * Plugin Name: CoreOptimize PC Tools
 * Description: Dark-themed PC tools (PSU, Bottleneck, Compatibility, FPS, Upgrade, AI Optimizer) with shortcodes and Elementor widgets. Admin settings for Gemini/OpenAI. Brand: CoreOptimize.
 * Version: 1.0.0
 * Author: CoreOptimize
 * Text Domain: coreoptimize-pc-tools
 */

if (!defined('ABSPATH')) { exit; }

class CoreOptimize_PCTools_Plugin {
    const OPTION_KEY = 'coreoptimize_tools_settings';
    const NONCE_KEY  = 'coreoptimize_tools_nonce';
    const VERSION    = '1.0.0';

    public function __construct() {
        add_action('init', [$this, 'register_assets']);
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('elementor/widgets/register', [$this, 'register_elementor_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'register_elementor_category']);
        add_shortcode('coreoptimize_tool', [$this, 'shortcode_tool']);
        add_shortcode('coreoptimize_tools_panel', [$this, 'shortcode_tools_panel']);
    }

    /* ---------------------- Settings ---------------------- */
    public static function get_settings(): array {
        $defaults = [
            'provider' => 'gemini', // gemini|openai
            'gemini_api_key' => '',
            'gemini_model' => 'gemini-1.5-flash',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o-mini',
            'cache_minutes' => 60,
            'brand_name' => 'CoreOptimize',
        ];
        $opts = get_option(self::OPTION_KEY, []);
        if (!is_array($opts)) $opts = [];
        return array_merge($defaults, $opts);
    }

    public function register_settings_page() {
        add_options_page(
            __('CoreOptimize Tools', 'coreoptimize-pc-tools'),
            __('CoreOptimize Tools', 'coreoptimize-pc-tools'),
            'manage_options',
            'coreoptimize-pc-tools',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => function($input) {
                $out = [];
                $out['provider'] = in_array($input['provider'] ?? 'gemini', ['gemini','openai'], true) ? $input['provider'] : 'gemini';
                $out['gemini_api_key'] = sanitize_text_field($input['gemini_api_key'] ?? '');
                $out['gemini_model'] = sanitize_text_field($input['gemini_model'] ?? 'gemini-1.5-flash');
                $out['openai_api_key'] = sanitize_text_field($input['openai_api_key'] ?? '');
                $out['openai_model'] = sanitize_text_field($input['openai_model'] ?? 'gpt-4o-mini');
                $out['cache_minutes'] = max(0, intval($input['cache_minutes'] ?? 60));
                $out['brand_name'] = sanitize_text_field($input['brand_name'] ?? 'CoreOptimize');
                return $out;
            }
        ]);
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;
        $opts = self::get_settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('CoreOptimize PC Tools Settings', 'coreoptimize-pc-tools'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::OPTION_KEY); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="provider">Provider</label></th>
                        <td>
                            <select name="<?php echo self::OPTION_KEY; ?>[provider]" id="provider">
                                <option value="gemini" <?php selected($opts['provider'], 'gemini'); ?>>Gemini</option>
                                <option value="openai" <?php selected($opts['provider'], 'openai'); ?>>OpenAI</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gemini_api_key">Gemini API Key</label></th>
                        <td><input type="password" id="gemini_api_key" name="<?php echo self::OPTION_KEY; ?>[gemini_api_key]" value="<?php echo esc_attr($opts['gemini_api_key']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gemini_model">Gemini Model</label></th>
                        <td><input type="text" id="gemini_model" name="<?php echo self::OPTION_KEY; ?>[gemini_model]" value="<?php echo esc_attr($opts['gemini_model']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="openai_api_key">OpenAI API Key</label></th>
                        <td><input type="password" id="openai_api_key" name="<?php echo self::OPTION_KEY; ?>[openai_api_key]" value="<?php echo esc_attr($opts['openai_api_key']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="openai_model">OpenAI Model</label></th>
                        <td><input type="text" id="openai_model" name="<?php echo self::OPTION_KEY; ?>[openai_model]" value="<?php echo esc_attr($opts['openai_model']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cache_minutes">Cache (minutes)</label></th>
                        <td><input type="number" min="0" id="cache_minutes" name="<?php echo self::OPTION_KEY; ?>[cache_minutes]" value="<?php echo esc_attr($opts['cache_minutes']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="brand_name">Brand Name</label></th>
                        <td><input type="text" id="brand_name" name="<?php echo self::OPTION_KEY; ?>[brand_name]" value="<?php echo esc_attr($opts['brand_name']); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Shortcodes: <code>[coreoptimize_tool type="psu|bottleneck|compatibility|fps|upgrade|optimizer"]</code>, <code>[coreoptimize_tools_panel]</code></p>
        </div>
        <?php
    }

    /* ---------------------- Assets ---------------------- */
    public function register_assets() {
        wp_register_style('coreoptimize-tools', plugins_url('assets/style.css', __FILE__), [], self::VERSION);
        wp_register_script('coreoptimize-tools', plugins_url('assets/frontend.js', __FILE__), ['wp-element'], self::VERSION, true);
        // Localize REST base
        wp_localize_script('coreoptimize-tools', 'CoreOptimizeTools', [
            'restBase' => esc_url_raw(rest_url('coreoptimize/v1/')),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
    }

    /* ---------------------- REST API ---------------------- */
    public function register_rest_routes() {
        $namespace = 'coreoptimize/v1';
        register_rest_route($namespace, '/psu', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'api_psu']
        ]);
        register_rest_route($namespace, '/bottleneck', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'api_bottleneck']
        ]);
        register_rest_route($namespace, '/compatibility', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'api_compatibility']
        ]);
        register_rest_route($namespace, '/fps', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'api_fps']
        ]);
        register_rest_route($namespace, '/upgrade', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'api_upgrade']
        ]);
        register_rest_route($namespace, '/optimizer', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'api_optimizer']
        ]);
    }

    /* ---------------------- Deterministic Tools ---------------------- */
    public function api_psu(WP_REST_Request $req) {
        $data = $this->sanitize_array($req->get_json_params());
        $cpu_tdp = intval($data['cpu_tdp'] ?? 65);
        $gpu_tdp = intval($data['gpu_tdp'] ?? 200);
        $drives = intval($data['drives'] ?? 1);
        $fans = intval($data['fans'] ?? 2);
        $headroom = floatval($data['headroom'] ?? 0.2); // 20%
        $base = $cpu_tdp + $gpu_tdp + ($drives * 8) + ($fans * 3) + 30; // mobo+ram misc
        $watt = (int) ceil($base * (1 + max(0, min($headroom, 1))));
        $tier = $this->psu_tier($watt);
        return $this->ok(['recommended_wattage' => $watt, 'tier' => $tier]);
    }

    private function psu_tier(int $w): string {
        if ($w <= 450) return 'Entry 450W';
        if ($w <= 650) return 'Mid 650W';
        if ($w <= 850) return 'Performance 850W';
        if ($w <= 1000) return 'High 1000W';
        return 'Enthusiast 1200W+';
    }

    public function api_bottleneck(WP_REST_Request $req) {
        $d = $this->sanitize_array($req->get_json_params());
        $cpu_score = floatval($d['cpu_score'] ?? 15000);
        $gpu_score = floatval($d['gpu_score'] ?? 12000);
        $res = sanitize_text_field($d['resolution'] ?? '1080p');
        $mult = $res === '1440p' ? 0.85 : ($res === '4k' ? 0.7 : 1.0);
        $ratio = ($cpu_score * $mult) / max(1, $gpu_score);
        $bottleneck = $ratio < 0.85 ? 'CPU-limited' : ($ratio > 1.15 ? 'GPU-limited' : 'Balanced');
        $pct = round(abs(1 - min($ratio, 1/$ratio)) * 100, 1);
        return $this->ok(['bottleneck' => $bottleneck, 'severity_percent' => $pct]);
    }

    public function api_compatibility(WP_REST_Request $req) {
        $d = $this->sanitize_array($req->get_json_params());
        $socket = sanitize_text_field($d['cpu_socket'] ?? 'AM5');
        $mb_socket = sanitize_text_field($d['mobo_socket'] ?? 'AM5');
        $ram_type = sanitize_text_field($d['ram_type'] ?? 'DDR5');
        $mobo_ram = sanitize_text_field($d['mobo_ram'] ?? 'DDR5');
        $gpu_len = intval($d['gpu_length_mm'] ?? 300);
        $case_gpu = intval($d['case_gpu_max_mm'] ?? 320);
        $ok = ($socket === $mb_socket) && ($ram_type === $mobo_ram) && ($gpu_len <= $case_gpu);
        $issues = [];
        if ($socket !== $mb_socket) $issues[] = 'CPU and motherboard sockets differ.';
        if ($ram_type !== $mobo_ram) $issues[] = 'RAM type not supported by motherboard.';
        if ($gpu_len > $case_gpu) $issues[] = 'GPU length exceeds case clearance.';
        return $this->ok(['compatible' => $ok, 'issues' => $issues]);
    }

    public function api_fps(WP_REST_Request $req) {
        $d = $this->sanitize_array($req->get_json_params());
        $gpu_score = floatval($d['gpu_score'] ?? 12000);
        $cpu_score = floatval($d['cpu_score'] ?? 15000);
        $game = sanitize_text_field($d['game'] ?? 'Generic');
        $res = sanitize_text_field($d['resolution'] ?? '1080p');
        $preset = sanitize_text_field($d['preset'] ?? 'high');
        $base = ($gpu_score * 0.004) + ($cpu_score * 0.001);
        $res_mult = ['1080p'=>1.0,'1440p'=>0.78,'4k'=>0.52][$res] ?? 1.0;
        $preset_mult = ['low'=>1.2,'medium'=>1.0,'high'=>0.85,'ultra'=>0.7][$preset] ?? 1.0;
        $fps = (int) max(15, round($base * 60 * $res_mult * $preset_mult));
        return $this->ok(['game'=>$game,'estimated_fps'=>$fps]);
    }

    public function api_upgrade(WP_REST_Request $req) {
        $d = $this->sanitize_array($req->get_json_params());
        $budget = intval($d['budget'] ?? 500);
        $target = sanitize_text_field($d['target'] ?? 'gaming');
        $current = [
            'cpu_score' => floatval($d['cpu_score'] ?? 10000),
            'gpu_score' => floatval($d['gpu_score'] ?? 8000),
            'ram_gb' => intval($d['ram_gb'] ?? 16),
            'storage_gb' => intval($d['storage_gb'] ?? 512)
        ];
        $recs = [];
        if ($current['gpu_score'] < 12000 && $budget >= 250) $recs[] = 'Upgrade GPU first for best gaming gains.';
        if ($current['cpu_score'] < 12000 && $budget >= 200) $recs[] = 'Consider a CPU with higher single-thread score.';
        if ($current['ram_gb'] < 16) $recs[] = 'Increase RAM to at least 16GB.';
        if ($current['storage_gb'] < 1000) $recs[] = 'Add a 1TB NVMe SSD for faster load times.';
        if (empty($recs)) $recs[] = 'Your build is well-balanced. Consider peripherals or cooling.';
        return $this->ok(['target'=>$target,'recommendations'=>$recs]);
    }

    /* ---------------------- AI Optimizer ---------------------- */
    public function api_optimizer(WP_REST_Request $req) {
        $d = $this->sanitize_array($req->get_json_params());
        $opts = self::get_settings();
        $payload = [
            'use_case' => sanitize_text_field($d['use_case'] ?? 'gaming'),
            'budget' => intval($d['budget'] ?? 1500),
            'preferences' => sanitize_text_field($d['preferences'] ?? ''),
            'current' => [
                'cpu' => sanitize_text_field($d['current']['cpu'] ?? ''),
                'gpu' => sanitize_text_field($d['current']['gpu'] ?? ''),
                'ram' => sanitize_text_field($d['current']['ram'] ?? ''),
                'storage' => sanitize_text_field($d['current']['storage'] ?? ''),
            ]
        ];
        $cache_key = 'coreopt_ai_' . md5(wp_json_encode($payload) . '|' . $opts['provider'] . '|' . $opts['gemini_model'] . '|' . $opts['openai_model']);
        $cached = get_transient($cache_key);
        if ($cached) {
            return $this->ok(['provider'=>$opts['provider'],'cached'=>true,'result'=>$cached]);
        }

        $prompt = $this->build_optimizer_prompt($payload, $opts['brand_name']);
        $result_text = '';
        if ($opts['provider'] === 'openai') {
            if (empty($opts['openai_api_key'])) return $this->err('OpenAI API key not set');
            $result_text = $this->call_openai($opts['openai_api_key'], $opts['openai_model'], $prompt);
        } else {
            if (empty($opts['gemini_api_key'])) return $this->err('Gemini API key not set');
            $result_text = $this->call_gemini($opts['gemini_api_key'], $opts['gemini_model'], $prompt);
        }
        if (is_wp_error($result_text)) return $result_text;
        set_transient($cache_key, $result_text, MINUTE_IN_SECONDS * max(0, intval($opts['cache_minutes'])));
        return $this->ok(['provider'=>$opts['provider'],'cached'=>false,'result'=>$result_text]);
    }

    private function build_optimizer_prompt(array $p, string $brand): string {
        $lines = [];
        $lines[] = "You are an expert PC build optimizer for {$brand}.";
        $lines[] = 'Task: Propose the best PC build plan given constraints. Output concise bullet points with rationale.';
        $lines[] = 'Constraints: prefer balanced CPU/GPU, consider PSU headroom, thermals, and upgrade path. Give 2 options: value and performance.';
        $lines[] = 'Inputs:';
        $lines[] = 'Use case: ' . $p['use_case'];
        $lines[] = 'Budget: $' . $p['budget'];
        $lines[] = 'Preferences: ' . $p['preferences'];
        $lines[] = 'Current: CPU ' . $p['current']['cpu'] . ', GPU ' . $p['current']['gpu'] . ', RAM ' . $p['current']['ram'] . ', Storage ' . $p['current']['storage'];
        $lines[] = 'Also include: estimated PSU wattage and expected 1080p FPS range for popular titles.';
        return implode("\n", $lines);
    }

    private function call_openai(string $api_key, string $model, string $prompt) {
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful PC hardware advisor.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.4,
            'max_tokens' => 600
        ];
        $res = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode($body),
            'timeout' => 30
        ]);
        if (is_wp_error($res)) return $res;
        $code = wp_remote_retrieve_response_code($res);
        $json = json_decode(wp_remote_retrieve_body($res), true);
        if ($code >= 400) return new WP_Error('openai_error', 'OpenAI error: ' . ($json['error']['message'] ?? $code));
        return $json['choices'][0]['message']['content'] ?? '';
    }

    private function call_gemini(string $api_key, string $model, string $prompt) {
        $url = add_query_arg(['key' => $api_key], 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent');
        $body = [
            'contents' => [[
                'parts' => [[ 'text' => $prompt ]]
            ]]
        ];
        $res = wp_remote_post($url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body' => wp_json_encode($body),
            'timeout' => 30
        ]);
        if (is_wp_error($res)) return $res;
        $code = wp_remote_retrieve_response_code($res);
        $json = json_decode(wp_remote_retrieve_body($res), true);
        if ($code >= 400) return new WP_Error('gemini_error', 'Gemini error: ' . ($json['error']['message'] ?? $code));
        // Extract text
        $text = '';
        if (!empty($json['candidates'][0]['content']['parts'])) {
            foreach ($json['candidates'][0]['content']['parts'] as $part) {
                if (!empty($part['text'])) $text .= $part['text'];
            }
        }
        return $text;
    }

    /* ---------------------- Shortcodes ---------------------- */
    public function shortcode_tool($atts) {
        $atts = shortcode_atts(['type' => 'psu'], $atts, 'coreoptimize_tool');
        $type = sanitize_key($atts['type']);
        wp_enqueue_style('coreoptimize-tools');
        wp_enqueue_script('coreoptimize-tools');
        ob_start();
        ?>
        <div class="coreoptimize-widget" data-type="<?php echo esc_attr($type); ?>">
            <?php echo $this->render_tool_form($type); ?>
            <div class="coreoptimize-results" aria-live="polite"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_tools_panel() {
        wp_enqueue_style('coreoptimize-tools');
        wp_enqueue_script('coreoptimize-tools');
        $tabs = ['psu'=>'PSU','bottleneck'=>'Bottleneck','compatibility'=>'Compatibility','fps'=>'FPS','upgrade'=>'Upgrade','optimizer'=>'AI Optimizer'];
        ob_start();
        ?>
        <div class="coreoptimize-panel">
            <div class="coreoptimize-tabs">
                <?php foreach ($tabs as $key=>$label): ?>
                    <button class="coreoptimize-tab" data-tab="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>
            <div class="coreoptimize-views">
                <?php foreach ($tabs as $key=>$label): ?>
                    <div class="coreoptimize-view" data-view="<?php echo esc_attr($key); ?>">
                        <?php echo $this->render_tool_form($key); ?>
                        <div class="coreoptimize-results" aria-live="polite"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_tool_form(string $type): string {
        $html = '';
        switch ($type) {
            case 'psu':
                $html .= '<label>CPU TDP (W) <input type="number" name="cpu_tdp" value="65" min="1"></label>';
                $html .= '<label>GPU TDP (W) <input type="number" name="gpu_tdp" value="200" min="1"></label>';
                $html .= '<label>Drives <input type="number" name="drives" value="1" min="0"></label>';
                $html .= '<label>Fans <input type="number" name="fans" value="2" min="0"></label>';
                $html .= '<label>Headroom <input type="number" name="headroom" value="0.2" step="0.05" min="0" max="1"></label>';
                break;
            case 'bottleneck':
                $html .= '<label>CPU Score <input type="number" name="cpu_score" value="15000"></label>';
                $html .= '<label>GPU Score <input type="number" name="gpu_score" value="12000"></label>';
                $html .= '<label>Resolution <select name="resolution"><option value="1080p">1080p</option><option value="1440p">1440p</option><option value="4k">4K</option></select></label>';
                break;
            case 'compatibility':
                $html .= '<label>CPU Socket <input type="text" name="cpu_socket" value="AM5"></label>';
                $html .= '<label>Motherboard Socket <input type="text" name="mobo_socket" value="AM5"></label>';
                $html .= '<label>RAM Type <input type="text" name="ram_type" value="DDR5"></label>';
                $html .= '<label>Mobo RAM Support <input type="text" name="mobo_ram" value="DDR5"></label>';
                $html .= '<label>GPU Length (mm) <input type="number" name="gpu_length_mm" value="300"></label>';
                $html .= '<label>Case GPU Max (mm) <input type="number" name="case_gpu_max_mm" value="320"></label>';
                break;
            case 'fps':
                $html .= '<label>GPU Score <input type="number" name="gpu_score" value="12000"></label>';
                $html .= '<label>CPU Score <input type="number" name="cpu_score" value="15000"></label>';
                $html .= '<label>Game <input type="text" name="game" value="Generic"></label>';
                $html .= '<label>Resolution <select name="resolution"><option value="1080p">1080p</option><option value="1440p">1440p</option><option value="4k">4K</option></select></label>';
                $html .= '<label>Preset <select name="preset"><option value="low">Low</option><option value="medium">Medium</option><option value="high" selected>High</option><option value="ultra">Ultra</option></select></label>';
                break;
            case 'upgrade':
                $html .= '<label>Budget ($) <input type="number" name="budget" value="500"></label>';
                $html .= '<label>Target <input type="text" name="target" value="gaming"></label>';
                $html .= '<label>CPU Score <input type="number" name="cpu_score" value="10000"></label>';
                $html .= '<label>GPU Score <input type="number" name="gpu_score" value="8000"></label>';
                $html .= '<label>RAM (GB) <input type="number" name="ram_gb" value="16"></label>';
                $html .= '<label>Storage (GB) <input type="number" name="storage_gb" value="512"></label>';
                break;
            case 'optimizer':
                $html .= '<label>Use Case <input type="text" name="use_case" value="gaming"></label>';
                $html .= '<label>Budget ($) <input type="number" name="budget" value="1500"></label>';
                $html .= '<label>Preferences <input type="text" name="preferences" placeholder="silence, RGB, small form factor"></label>';
                $html .= '<fieldset class="group"><legend>Current Build</legend>';
                $html .= '<label>CPU <input type="text" name="current[cpu]" placeholder="Ryzen 5 5600"></label>';
                $html .= '<label>GPU <input type="text" name="current[gpu]" placeholder="RTX 3060"></label>';
                $html .= '<label>RAM <input type="text" name="current[ram]" placeholder="16GB DDR4"></label>';
                $html .= '<label>Storage <input type="text" name="current[storage]" placeholder="1TB SSD"></label>';
                $html .= '</fieldset>';
                break;
        }
        $html .= '<button class="coreoptimize-submit" type="button">Calculate</button>';
        return '<form class="coreoptimize-form" data-endpoint="' . esc_attr($type) . '">' . $html . '</form>';
    }

    /* ---------------------- Elementor ---------------------- */
    public function register_elementor_category($elements_manager) {
        $elements_manager->add_category(
            'coreoptimize-category',
            [ 'title' => __('CoreOptimize', 'coreoptimize-pc-tools'), 'icon' => 'fa fa-microchip' ],
            1
        );
    }

    public function register_elementor_widgets($widgets_manager) {
        if (!class_exists('Elementor\Widget_Base')) return;
        require_once __FILE__;
        $widgets = [
            'psu' => 'PSU',
            'bottleneck' => 'Bottleneck',
            'compatibility' => 'Compatibility',
            'fps' => 'FPS',
            'upgrade' => 'Upgrade',
            'optimizer' => 'AI Optimizer',
        ];
        foreach ($widgets as $key => $label) {
            $widgets_manager->register(new class($key, $label) extends \Elementor\Widget_Base {
                private $tool_key; private $label;
                public function __construct($key, $label, $data = [], $args = null) { $this->tool_key = $key; $this->label = $label; parent::__construct($data, $args); }
                public function get_name() { return 'coreoptimize_' . $this->tool_key; }
                public function get_title() { return $this->label; }
                public function get_icon() { return 'eicon-tools'; }
                public function get_categories() { return ['coreoptimize-category']; }
                protected function register_controls() {
                    $this->start_controls_section('content_section', [ 'label' => __('Content', 'coreoptimize-pc-tools') ]);
                    $this->add_control('title', [ 'label' => __('Title', 'coreoptimize-pc-tools'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $this->label ]);
                    $this->end_controls_section();
                }
                protected function render() {
                    echo '<div class="coreoptimize-el-widget">';
                    $title = $this->get_settings_for_display('title');
                    if ($title) echo '<h3 class="coreoptimize-title">' . esc_html($title) . '</h3>';
                    echo do_shortcode('[coreoptimize_tool type="' . esc_attr($this->tool_key) . '"]');
                    echo '</div>';
                }
            });
        }
        // Combined panel widget
        $widgets_manager->register(new class extends \Elementor\Widget_Base {
            public function get_name() { return 'coreoptimize_panel'; }
            public function get_title() { return 'PC Tools Panel'; }
            public function get_icon() { return 'eicon-tabs'; }
            public function get_categories() { return ['coreoptimize-category']; }
            protected function register_controls() {}
            protected function render() { echo do_shortcode('[coreoptimize_tools_panel]'); }
        });
    }

    /* ---------------------- Utilities ---------------------- */
    private function sanitize_array($arr) {
        if (!is_array($arr)) return [];
        $out = [];
        foreach ($arr as $k=>$v) {
            if (is_array($v)) { $out[$k] = $this->sanitize_array($v); }
            elseif (is_numeric($v)) { $out[$k] = $v + 0; }
            else { $out[$k] = sanitize_text_field((string)$v); }
        }
        return $out;
    }

    private function ok($data) { return new WP_REST_Response(['success'=>true,'data'=>$data], 200); }
    private function err($msg, $code=400) { return new WP_REST_Response(['success'=>false,'error'=>$msg], $code); }
}

new CoreOptimize_PCTools_Plugin();

/* ---------------------- Inline Assets ---------------------- */
// Provide basic dark theme CSS and minimal JS to submit forms via REST
add_action('init', function() {
    // Create asset files on the fly if they don't exist (helps with manual single-file installs)
    $base = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($base)) { wp_mkdir_p($base); }
    $css = "/* CoreOptimize dark theme */\n.coreoptimize-panel, .coreoptimize-widget, .coreoptimize-el-widget {\n  background:#0b0f1a; color:#cbd5e1; border:1px solid #1e293b; border-radius:12px; padding:16px; box-shadow:0 0 0 1px rgba(59,130,246,.1) inset;\n}\n.coreoptimize-tabs { display:flex; gap:8px; margin-bottom:12px;}\n.coreoptimize-tab { background:#0f172a; color:#93c5fd; border:1px solid #1e293b; padding:8px 12px; border-radius:8px; cursor:pointer;}\n.coreoptimize-tab.active { background:linear-gradient(135deg, #3b82f6, #8b5cf6); color:white;}\n.coreoptimize-view { display:none;}\n.coreoptimize-view.active { display:block;}\n.coreoptimize-form { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:10px; }\n.coreoptimize-form label { display:flex; flex-direction:column; font-size:12px; color:#93a4b8;}\n.coreoptimize-form input, .coreoptimize-form select { background:#0f172a; color:#e2e8f0; border:1px solid #1f2937; padding:8px; border-radius:8px;}\n.coreoptimize-submit { grid-column:1/-1; padding:10px 12px; background:linear-gradient(135deg,#2563eb,#7c3aed); color:white; border:none; border-radius:10px; cursor:pointer;}\n.coreoptimize-results { margin-top:12px; background:#0a0f19; border:1px dashed #374151; padding:12px; border-radius:10px; white-space:pre-wrap;}\n.coreoptimize-title { margin-top:0; color:#a5b4fc;}\nfieldset.group { border:1px solid #1e293b; border-radius:10px; padding:8px; }\n";
    $js = "(function(){\n function qs(s,el){return (el||document).querySelector(s)}; function qsa(s,el){return (el||document).querySelectorAll(s)};\n function post(url, data){ return fetch(url,{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)}).then(r=>r.json()); }\n function collect(form){ const o={}; new FormData(form).forEach((v,k)=>{ if(k.includes('[')){ // nested
   const m=k.match(/(.*)\[(.*)\]/); if(m){ o[m[1]]=o[m[1]]||{}; o[m[1]][m[2]]=v; } } else { o[k]=isFinite(v)&&v!==''? Number(v): v; } }); return o; }\n function renderResult(el, res){ el.textContent = JSON.stringify(res.data||res, null, 2); }\n // Tabs
 qsa('.coreoptimize-panel').forEach(panel=>{ const tabs=qsa('.coreoptimize-tab', panel); const views=qsa('.coreoptimize-view', panel); function act(key){ tabs.forEach(t=>t.classList.toggle('active', t.dataset.tab===key)); views.forEach(v=>v.classList.toggle('active', v.dataset.view===key)); } act('psu'); tabs.forEach(t=>t.addEventListener('click', ()=>act(t.dataset.tab))); });\n // Forms
 qsa('.coreoptimize-form').forEach(form=>{ const wrap=form.closest('.coreoptimize-widget, .coreoptimize-view'); const out=qs('.coreoptimize-results', wrap); const ep=form.dataset.endpoint; form.querySelector('.coreoptimize-submit').addEventListener('click', ()=>{ const data=collect(form); form.classList.add('busy'); out.textContent='Calculating...'; post((window.CoreOptimizeTools?CoreOptimizeTools.restBase:'')+ep, data).then(res=>{ renderResult(out,res); }).catch(e=>{ out.textContent='Error: '+e; }).finally(()=>form.classList.remove('busy')); }); });\n})();";
    if (!file_exists($base.'style.css')) { file_put_contents($base.'style.css', $css); }
    if (!file_exists($base.'frontend.js')) { file_put_contents($base.'frontend.js', $js); }
});
