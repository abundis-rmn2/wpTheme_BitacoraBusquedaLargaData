<?php

function bitacora_busqueda_widgets() {
    register_sidebar(array(
        'name'          => __('Encabezado', 'bitacorabusqueda'),
        'id'            => 'header-widget',
        'description'   => __('Los widgets en esta area se muestran en el encabezado.', 'bitacorabusqueda'),
        'before_widget' => '<div class="header-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => __('Pie de Pagina 1', 'bitacorabusqueda'),
        'id'            => 'footer-widget-1',
        'description'   => __('Los widgets en esta area se muestran en el primer pie de pagina.', 'bitacorabusqueda'),
        'before_widget' => '<div class="footer-widget footer-widget-1">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => __('Pie de Pagina 2', 'bitacorabusqueda'),
        'id'            => 'footer-widget-2',
        'description'   => __('Los widgets en esta area se muestran en el segundo pie de pagina.', 'bitacorabusqueda'),
        'before_widget' => '<div class="footer-widget footer-widget-2">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => __('Pie de Pagina 3', 'bitacorabusqueda'),
        'id'            => 'footer-widget-3',
        'description'   => __('Los widgets en esta area se muestran en el tercer pie de pagina.', 'bitacorabusqueda'),
        'before_widget' => '<div class="footer-widget footer-widget-3">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'bitacora_busqueda_widgets');
