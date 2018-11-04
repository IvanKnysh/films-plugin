<?php
/*
Plugin Name: Films
Description: Плагин - "Каталог фильмов".
Version: 1.0
Author: Иван Кныш
Author URI: https://www.facebook.com/ivan.knysh.7
*/
?>
<?php
/*  Copyright 2018  Иван Кныш  (email: mr.rauber.ik@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
// Подключение стилей к плагину
function films_style() {
	wp_register_style( 'style.css', plugins_url('assets/css/style.css', __FILE__) );
    wp_enqueue_style( 'style.css' );
}
add_action( 'wp_enqueue_scripts', 'films_style' );



// Активация плагина
register_activation_hook( __FILE__, 'films_install' ); 
function films_install(){
	// Запускаем функцию регистрации типа записи
	films();

	// Сбрасываем настройки ЧПУ, чтобы они пересоздались с новыми данными
	flush_rewrite_rules();
}

// Деактивация плагина
register_deactivation_hook( __FILE__, 'films_deactivation' );
function films_deactivation() {

	// Сбрасываем настройки ЧПУ, чтобы они пересоздались с новыми данными
	flush_rewrite_rules();
}



// Создание произвольного типа записи Films
add_action( 'init', 'films' );
function films(){
	register_post_type('films', array(
		'label'  => null,
		'labels' => array(
			'name'               => 'Фильмы',
			'singular_name'      => 'Films', // название для одной записи этого типа
			'add_new'            => 'Добавить фильм', // для добавления новой записи
			'add_new_item'       => 'Добавить фильм', // заголовка у вновь создаваемой записи в админ-панели.
			'edit_item'          => 'Редактирование фильма', // для редактирования типа записи
			'new_item'           => 'Новая запись', // текст новой записи
			'view_item'          => 'Смотреть', // для просмотра записи этого типа.
			'search_items'       => 'Искать фильмы', // для поиска по этим типам записи
			'not_found'          => 'Фильм не найдено', // если в результате поиска ничего не было найдено
			'not_found_in_trash' => 'Фильм не найдено в корзине', // если не было найдено в корзине
			'parent_item_colon'  => '', // для родителей (у древовидных типов)
			'menu_name'          => 'Каталог фильмов', // название меню
		),
		'description'         => 'Каталог фильмов',
		'public'              => true,
		'publicly_queryable'  => true, // зависит от public
		'exclude_from_search' => false, // зависит от public
		'show_ui'             => true, // зависит от public
		'show_in_menu'        => true, // показывать ли в меню адмнки
		'show_in_admin_bar'   => true, // по умолчанию значение show_in_menu
		'show_in_nav_menus'   => true, // зависит от public
		'show_in_rest'        => true, // добавить в REST API. C WP 4.7
		'rest_base'           => true, // $post_type. C WP 4.7
		'menu_position'       => 75,
		'menu_icon'           => 'dashicons-video-alt', 
		'hierarchical'        => false,
		'supports'            => array('title','editor','thumbnail'),
		'taxonomies'          => array('films_cat'),
		'has_archive'         => true,
		'rewrite'             => true,
		'query_var'           => true,
	) );
}


// регистрируем таксономию 'Animal Family'
function wptp_register_taxonomy() {
    register_taxonomy( 'films_cat', 'films',
        array(
            'labels' => array(
                'name'              => 'Категории фильмов',
                'singular_name'     => 'Категории фильмов',
                'search_items'      => 'Поиск категорий',
                'all_items'         => 'Все категории',
                'edit_item'         => 'Изменть категорию',
                'update_item'       => 'Обновить категорию',
                'add_new_item'      => 'Добавить новую категорию',
                'new_item_name'     => 'Имя новой категории',
                'menu_name'         => 'Категории',
            ),
            'hierarchical' => true,
            'sort' => true,
            'args' => array( 'orderby' => 'term_order' ),
            'rewrite' => array( 'slug' => 'films_cat' ),
            'show_admin_column' => true
        )
    );
}
add_action( 'init', 'wptp_register_taxonomy' );



// Archive template folder
add_filter( 'archive_template', 'override_archive_template' );
function override_archive_template( $archive_template ){
    global $post;

    $file = dirname(__FILE__) .'/templates/archive-'. $post->post_type .'.php';

    if( file_exists( $file ) ) $archive_template = $file;

    return $archive_template;
}


// Создание метабокса
add_action('add_meta_boxes', 'my_extra_fields', 1);

function my_extra_fields() {
	add_meta_box( 'extra_fields', 'Дополнительные данные', 'extra_fields_box_func', 'films', 'normal', 'high'  );
}
// код блока
function extra_fields_box_func( $post ){
	?>
	<p><label>Стоимость сеанса: </label>
		<input type="text" name="extra[title]" value="<?php echo get_post_meta($post->ID, 'title', 1); ?>" placeholder="200 грн."></p>
	<p><label>Дата выхода в прокат: </label>
		<input type="text" name="extra[data]" value="<?php echo get_post_meta($post->ID, 'data', 1); ?>" placeholder="31.11.2018"></p>

	<input type="hidden" name="extra_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />
	<?php
}

// включаем обновление полей при сохранении
add_action( 'save_post', 'my_extra_fields_update', 0 );

## Сохраняем данные, при сохранении поста
function my_extra_fields_update( $post_id ){
	// базовая проверка
	if (
		   empty( $_POST['extra'] )
		|| ! wp_verify_nonce( $_POST['extra_fields_nonce'], __FILE__ )
		|| wp_is_post_autosave( $post_id )
		|| wp_is_post_revision( $post_id )
	)
		return false;

	// Сохранить/удалить данные
	$_POST['extra'] = array_map( 'sanitize_text_field', $_POST['extra'] ); // чистим все данные от пробелов по краям
	foreach( $_POST['extra'] as $key => $value ){
		if( empty($value) ){
			delete_post_meta( $post_id, $key ); // удаляем поле если значение пустое
			continue;
		}

		update_post_meta( $post_id, $key, $value ); // add_post_meta() работает автоматически
	}

	return $post_id;
}



// Раздел "помощь" типа записи films
add_action( 'contextual_help', 'add_help_text', 10, 3 );
function add_help_text( $contextual_help, $screen_id, $screen ){
	if('films' == $screen->id ) {
		$contextual_help = '
		<h2>Для добавления фильма в каталог:</h2>
		<ul>
			<li>Введите заголовок</li>
			<li>Введите описание фильма</li>
			<li>Введите стоимость сеанса</li>
			<li>Введите дату выхода в прокат</li>
			<li>Установите изображение фильма</li>
			<li>Выберите созданную ранее категорию для фильма</li>
			<li>Нажмите "Опубликовать"</li>
		</ul>
		';
	}
	elseif( 'edit-films' == $screen->id ) {
		$contextual_help = '
		<h2>Для добавления фильма в каталог:</h2>
		<ul>
			<li>Нажать на пункт меню "Добавить фильм"</li>
		</ul>
		';
	}

	return $contextual_help;
}


add_filter('the_content', 'dco_the_content');
function dco_the_content( $content ){
    if (get_post_meta(get_the_ID(), 'title', true) || get_post_meta(get_the_ID(), 'data', true)) {
        $content .= '<label class="p1">Стоимость: ' . get_post_meta(get_the_ID(), 'title', true) . '</label>';
        $content .= '<label class="p2">Дата выхода: ' . get_post_meta(get_the_ID(), 'data', true) . '</label>';
    }
	the_terms( $post->ID, 'films_cat', '<span class="term">', ', ','</span>' );
	

    return $content;
}


?>
