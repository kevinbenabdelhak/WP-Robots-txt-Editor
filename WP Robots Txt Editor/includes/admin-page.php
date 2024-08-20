<?php
// Sécurisation: Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

function register_robots_txt_page() {
    add_options_page(
        'Modifier le fichier robots.txt',
        'Robots.txt',
        'manage_options',
        'robots_txt_page',
        'display_robots_txt_page'
    );
}

function display_robots_txt_page() {
    if (!current_user_can('administrator')) {
        return;
    }

    // Définir le chemin du fichier robots.txt
    $robots_txt_file = ABSPATH . 'robots.txt';

    // Initialiser le contenu de robots.txt comme vide par défaut
    $robots_txt_content = "";

    // Vérifier l'existence du fichier robots.txt avant lecture ou modification
    if (file_exists($robots_txt_file)) {
        $robots_txt_content = file_get_contents($robots_txt_file);
    } else {
        echo '<div class="error"><p>Vous n\'avez pas encore de fichier robots.txt, vous pouvez le créer ici 👇</p></div>';
    }
if (isset($_POST['robots_txt_content'])) {
    $robots_txt_content = stripslashes($_POST['robots_txt_content']);
    
    // Supprimez la vérification des directives non reconnues 
    file_put_contents($robots_txt_file, $robots_txt_content);
    echo '<div class="updated"><p>Le fichier robots.txt a été modifié avec succès.</p></div>';
}

    if (isset($_POST['robots_txt_content_types'])) {
        update_option('robots_txt_content_types', $_POST['robots_txt_content_types']);
    }

    $user_agents = [
        'Tous' => ['*'],
        'Populaires' => [
            'Googlebot', 'Bingbot', 'YandexBot', 'Baiduspider', 'DuckDuckBot'
        ],
        'Moteur de recherche' => [
            'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider',
            'YandexBot', 'Sogou', 'Exabot', 'FacebookBot', 'Twitterbot', 'Google-Extended'
        ],
        'Autres outils (scraper)' => [
            'SemrushBot', 'MJ12bot', 'AhrefsBot', 'BLEXBot', 'DotBot',
            'Screaming Frog', 'Python-urllib', 'HTTrack', 'LinkpadBot', 'MegaIndex.ru'
        ],
        'Média' => [
            'Googlebot-Image', 'Googlebot-Video',
            'Pinterestbot', 'Applebot'
        ]
    ];

    $file_types = [
        'Images' => ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.svg', '.webp'],
        'Vidéos' => ['.mp4', '.avi', '.mov', '.wmv', '.flv', '.mkv'],
        'Documents' => ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx'],
        'Audios' => ['.mp3', '.wav', '.ogg', '.m4a'],
        'Autres' => ['.zip', '.rar', '.7z', '.exe', '.iso']
    ];

    $posts = get_posts(['posts_per_page' => -1]);
    $pages = get_pages();
    
    $categories = get_categories(['hide_empty' => false]);

    $parent_pages = array_filter($pages, function($page) {
        return $page->post_parent == 0;
    });

    $post_types = get_post_types(['public' => true], 'objects');
    $selected_post_types = get_option('robots_txt_content_types', []);
    ?>
    <div class="wrap">
        <h2>Modifier robots.txt</h2>
        <div class="columns">
            <div class="column">
                <h2>Contenu du robots.txt</h2>
                <form method="post" id="robots_txt_form">
                    <textarea name="robots_txt_content" id="robots_txt_content" cols="80" rows="20"><?php echo esc_textarea($robots_txt_content); ?></textarea>
                    <div id="validation_errors" style="color: red; margin-top: 10px;"></div><br>
                    <input type="submit" value="Enregistrer les modifications" class="button-primary" style="margin-top: 20px;">
                    <?php 
                    $site_url = get_site_url();
                    $parsed_url = parse_url($site_url);
                    $domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                    $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] : 'http';
                    ?>
                    <input type="button" value="Ouvrir Google Search Console" class="button-secondary" style="margin-top: 20px; margin-left: 10px;" onclick="window.open('https://search.google.com/search-console/settings/robots-txt?resource_id=<?php echo esc_js("{$scheme}://{$domain}"); ?>&hl=fr', '_blank');">
                </form>
            </div>
            <div class="column">
                <h2>Suggestions</h2>
                <button type="button" id="generate-default" class="button">Générer robots.txt par défaut</button>
                
                <!-- User-Agent Suggestions -->
                <div class="user-agent-suggestions">
                    <h3>User-Agent
                    <span class="dashicons dashicons-info inline-help" title="Les User-Agents sont des identifiants pour les robots des moteurs de recherche."></span></h3>
                    <select id="user-agent-select">
                        <option value="" disabled selected>Choisir un user-Agent</option>
                        <optgroup label="Tous">
                            <option value="*">Tous</option>
                        </optgroup>
                        <?php foreach ($user_agents as $category => $agents) : ?>
                            <?php if ($category !== 'Tous') : ?>
                                <optgroup label="<?php echo esc_attr($category); ?>">
                                    <?php foreach ($agents as $agent) : ?>
                                        <option value="<?php echo esc_attr($agent); ?>"><?php echo esc_html($agent); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="insert-user-agent" class="button">Insérer</button>
                </div>

                <!-- Disallow/Allow Publications/Pages -->
                <div class="disallow-allow">
                    <h3>Publications
                    <span class="dashicons dashicons-info inline-help" title="Sélectionnez une publication ou une page à ajouter dans Disallow/Allow."></span></h3>
                    <select id="disallow-allow-select">
                        <option value="" disabled selected>Choisir une publication</option>
                        <?php foreach (array_merge($posts, $pages) as $post_page) : ?>
                            <option value="<?php echo esc_url(get_permalink($post_page->ID)); ?>"><?php echo esc_html($post_page->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-disallow" class="button">Ajouter en Disallow</button>
                    <button type="button" id="add-allow" class="button">Ajouter en Allow</button>
                </div>

                <!-- Disallow/Allow Catégories -->
                <div class="category-disallow-allow">
                    <h3>Catégories
                    <span class="dashicons dashicons-info inline-help" title="Sélectionnez une catégorie à ajouter dans Disallow/Allow."></span></h3>
                    <select id="category-disallow-allow-select">
                        <option value="" disabled selected>Choisir une catégorie</option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>"><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-category-disallow" class="button">Ajouter en Disallow</button>
                    <button type="button" id="add-category-allow" class="button">Ajouter en Allow</button>
                </div>

                <!-- Disallow/Allow Pages Parents + Enfants -->
                <div class="parent-disallow-allow">
                    <h3>Parent + Enfants
                    <span class="dashicons dashicons-info inline-help" title="Sélectionnez une page parent pour ajouter toutes ses pages enfants dans Disallow/Allow."></span></h3>
                    <select id="parent-disallow-allow-select">
                        <option value="" disabled selected>Choisir une Page Parent</option>
                        <?php foreach ($parent_pages as $parent_page) : ?>
                            <option value="<?php echo esc_url(get_permalink($parent_page->ID)); ?>"><?php echo esc_html($parent_page->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-parent-disallow" class="button">Ajouter en Disallow</button>
                    <button type="button" id="add-parent-allow" class="button">Ajouter en Allow</button>
                </div>

                <!-- Disallow/Allow Types de Fichiers -->
                <div class="file-type-disallow-allow">
                    <h3>Types de fichiers
                    <span class="dashicons dashicons-info inline-help" title="Sélectionnez un type de fichier à ajouter dans Disallow/Allow."></span></h3>
                    <select id="file-type-disallow-allow-select">
                        <option value="" disabled selected>Choisir un Type de Fichier</option>
                        <?php foreach ($file_types as $category => $extensions) : ?>
                            <optgroup label="<?php echo esc_attr($category); ?>">
                                <?php foreach ($extensions as $extension) : ?>
                                    <option value="<?php echo esc_attr($extension); ?>"><?php echo esc_html($extension); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-file-disallow" class="button">Ajouter en Disallow</button>
                    <button type="button" id="add-file-allow" class="button">Ajouter en Allow</button>
                </div>

                <!-- Sitemaps et Flux RSS -->
                <div class="sitemap-rss">
                    <h3>Sitemaps et Flux RSS
                    <span class="dashicons dashicons-info inline-help" title="Sélectionnez un sitemap ou flux RSS pour ajouter dans Disallow/Allow."></span></h3>
                    <select id="sitemap-rss-select">
                        <option value="" disabled selected>Choisir un Sitemap ou Flux RSS</option>
                        <optgroup label="Sitemaps">
                            <option value="<?php echo esc_url(site_url('/wp-sitemap.xml')); ?>">Sitemap par défaut (wp-sitemap.xml)</option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_url(site_url('/sitemap-' . $category->slug . '.xml')); ?>">Sous-sitemap (<?php echo esc_html($category->name); ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Flux RSS">
                            <option value="<?php echo esc_url(get_bloginfo('rss2_url')); ?>">Feed principal</option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_url(get_category_feed_link($category->term_id)); ?>">Feed catégorie (<?php echo esc_html($category->name); ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <button type="button" id="add-sitemap-rss-disallow" class="button">Ajouter en Disallow</button>
                    <button type="button" id="add-sitemap-rss-allow" class="button">Ajouter en Allow</button>
                </div>

            </div>
        </div>
    </div>

   <script>
document.querySelectorAll('.inline-help').forEach(function(element) {
    element.addEventListener('mouseover', function(e) {
        var tooltip = document.createElement('div');
        tooltip.className = 'help-tooltip';
        tooltip.innerText = e.target.title;
        document.body.appendChild(tooltip);
        var rect = e.target.getBoundingClientRect();
        tooltip.style.top = rect.top + window.scrollY + 'px';
        tooltip.style.left = (rect.left + rect.width + 10) + 'px';
        tooltip.style.display = 'block';
    });

    element.addEventListener('mouseout', function() {
        document.querySelectorAll('.help-tooltip').forEach(function(tooltip) {
            tooltip.remove();
        });
    });
});

document.getElementById('insert-user-agent').addEventListener('click', function() {
    var select = document.getElementById('user-agent-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        textarea.value += 'User-agent: ' + selected + '\n';
    }
});

document.getElementById('add-disallow').addEventListener('click', function() {
    var select = document.getElementById('disallow-allow-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        var url = new URL(selected);
        textarea.value += 'Disallow: ' + url.pathname + '\n';
    }
});

document.getElementById('add-allow').addEventListener('click', function() {
    var select = document.getElementById('disallow-allow-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        var url = new URL(selected);
        textarea.value += 'Allow: ' + url.pathname + '\n';
    }
});

document.getElementById('add-category-disallow').addEventListener('click', function() {
    var select = document.getElementById('category-disallow-allow-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        textarea.value += 'Disallow: /category/' + selected + '/*\n';
    }
});

document.getElementById('add-category-allow').addEventListener('click', function() {
    var select = document.getElementById('category-disallow-allow-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        textarea.value += 'Allow: /category/' + selected + '/*\n';
    }
});

document.getElementById('add-parent-disallow').addEventListener('click', function() {
    var select = document.getElementById('parent-disallow-allow-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        var url = new URL(selected);
        textarea.value += 'Disallow: ' + url.pathname + '*\n';
    }
});

document.getElementById('add-parent-allow').addEventListener('click', function() {
    var select = document.getElementById('parent-disallow-allow-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        var url = new URL(selected);
        textarea.value += 'Allow: ' + url.pathname + '*\n';
    }
});

document.getElementById('add-file-disallow').addEventListener('click', function() {
    var select = document.getElementById('file-type-disallow-allow-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        textarea.value += 'Disallow: *' + selected + '\n';
    }
});

document.getElementById('add-file-allow').addEventListener('click', function() {
    var select = document.getElementById('file-type-disallow-allow-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        textarea.value += 'Allow: *' + selected + '\n';
    }
});

document.getElementById('add-sitemap-rss-disallow').addEventListener('click', function() {
    var select = document.getElementById('sitemap-rss-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        var url = new URL(selected);
        textarea.value += 'Disallow: ' + url.pathname + '\n';
    }
});

document.getElementById('add-sitemap-rss-allow').addEventListener('click', function() {
    var select = document.getElementById('sitemap-rss-select');
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
    var selected = select.value;
    if (selected) {
        var url = new URL(selected);
        textarea.value += 'Allow: ' + url.pathname + '\n';
    }
});



document.getElementById('generate-default').addEventListener('click', function() {
    var textarea = document.querySelector('textarea[name="robots_txt_content"]');
  var defaultContent = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n" +
"# Empêcher l'indexation des répertoires sensibles\nDisallow: /wp-includes\nDisallow: /wp-content/plugins\nDisallow: /wp-content/cache\nDisallow: /trackback\nDisallow: /comments\nDisallow: /category/*/*\nDisallow: */trackback\nDisallow: */comments\n" +
"# Désindexer toutes les URL avec des paramètres pour éviter la duplication de contenu\nDisallow: /*?*\nDisallow: /*?\n" +
"# Empêcher l'indexation de la page de connexion\nDisallow: /wp-login.php\n" +
"# Autoriser l'indexation des images\nAllow: /wp-content/uploads\n" +
"User-agent: Googlebot\n" +
"# Empêcher l'indexation des fichiers sensibles\nDisallow: /*.php$\nDisallow: /*.inc$\nDisallow: /*.gz$\n" +
"# Autoriser l'indexation des PDF\nAllow: /*.pdf$\n" +
"User-agent: Googlebot-Image\nDisallow:\nAllow: /*\n" +
"User-agent: Mediapartners-Google*\nDisallow:\nAllow: /*\n";
    
    var privatePagesDisallow = "";
   <?php
// Utilisez ce bloc PHP pour obtenir les pages privées et protégées par mot de passe
$private_pages = get_pages(array(
    'post_status' => 'private'
));

$password_protected_pages = get_posts(array(
    'post_type' => 'page',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_post_password',
            'value' => '',
            'compare' => '!='
        )
    )
));

foreach ($private_pages as $private_page) {
    $private_url = parse_url(get_permalink($private_page->ID), PHP_URL_PATH);
    echo "privatePagesDisallow += 'Disallow: {$private_url}\\n';";
}

foreach ($password_protected_pages as $password_protected_page) {
    $protected_url = parse_url(get_permalink($password_protected_page->ID), PHP_URL_PATH);
    echo "privatePagesDisallow += 'Disallow: {$protected_url}\\n';";
}
?>
    // Concatenate private pages disallow rules
    defaultContent += privatePagesDisallow;
    textarea.value = defaultContent;
});


</script>
    <?php
}

?>
