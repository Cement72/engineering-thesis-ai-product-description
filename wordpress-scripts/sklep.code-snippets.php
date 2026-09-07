<?php

/**
 * Panel generowania
 */
// Rejestracja zakładki w menu głównym WordPressa
add_action( 'admin_menu', 'ai_ingestion_register_menu_v2' );
function ai_ingestion_register_menu_v2() {
    $page_hook = add_menu_page(
        'Generator opisów',                
        'Generator opisów',        
        'manage_options',        
        'ai-generator-panel',    
        'ai_ingestion_render_page_v2', 
        'dashicons-images-alt2', 
        57                       
    );
    add_action( 'load-' . $page_hook, 'ai_ingestion_load_wp_media_scripts' );
}

function ai_ingestion_load_wp_media_scripts() {
    wp_enqueue_media();
}

// Wygląd i interfejs panelu masowego wgrywania zdjęć
function ai_ingestion_render_page_v2() {
    ?>
    <div class="wrap ai-gen-container">
        <h1>Generator opisów produktów AI</h1>
        
        <div style="background: #fff; padding: 25px; border: 1px solid #ccd0d4; border-radius: 8px; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0; text-align: center;">Krok 1: Wybierz pliki lub wgraj folder z dysku</h2>
            
            <div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 25px; flex-wrap: wrap;">
                <button type="button" class="button button-secondary button-large" id="ai_select_photos_btn">
                    <span class="dashicons dashicons-admin-media"></span>
                    Wybierz z biblioteki WP
                </button>

                <input type="file" id="ai_folder_input" webkitdirectory directory multiple accept="image/*" style="display: none;">
                <button type="button" class="button button-primary button-large" id="ai_select_folder_btn" style="background: #2271b1; border-color: #2271b1;">
                    <span class="dashicons dashicons-category"></span>
                    Wgraj folder z dysku
                </button>
            </div>

            <style>
                .ai-gen-container #ai_select_photos_btn .dashicons,
                .ai-gen-container #ai_select_folder_btn .dashicons {
                    margin-top: 0px !important;
                    vertical-align: top !important;
                    font-size: 20px !important;
                    width: 20px !important;
                    height: 20px !important;
                    margin-right: 6px !important;
                }

                #ai_workspace { display: none; }

                .ai-upload-grid-container {
                    display: grid;
                    grid-template-columns: 200px 1fr;
                    gap: 20px;
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    border: 1px dashed #c3c4c7;
                }
                .ai-zone-box {
                    background: #fff;
                    border: 2px dashed #ddd;
                    border-radius: 6px;
                    padding: 10px;
                    min-height: 120px;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    align-content: flex-start;
                    transition: background 0.2s, border-color 0.2s;
                }
                .ai-zone-box.drag-over {
                    background: #eef5fc !important;
                    border-style: solid !important;
                }
                .ai-zone-box.zone-main { border-color: #9b51e0; background: #fcfaff; }
                .ai-zone-box.zone-ai { border-color: #2271b1; background: #f4f8fb; }
                .ai-zone-box.zone-gallery { border-color: #ccc; }

                .ai-zone-title {
                    font-weight: bold;
                    font-size: 12px;
                    text-transform: uppercase;
                    margin-bottom: 8px;
                    display: block;
                }
                .title-main { color: #9b51e0; }
                .title-ai { color: #2271b1; }
                .title-gallery { color: #646970; }

                .ai-thumb-card {
                    position: relative;
                    width: 90px;
                    height: 90px;
                    border-radius: 6px;
                    overflow: hidden;
                    border: 1px solid #ccc;
                    background: #fff;
                    cursor: grab;
                }
                .ai-thumb-card:active { cursor: grabbing; }
                .ai-thumb-card img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    pointer-events: none;
                }
                .ai-thumb-card .ai-card-remove {
                    position: absolute;
                    top: 3px;
                    right: 3px;
                    background: #d63638;
                    color: #fff;
                    border-radius: 50%;
                    width: 18px;
                    height: 18px;
                    text-align: center;
                    line-height: 16px;
                    font-size: 12px;
                    font-weight: bold;
                    display: none;
                    z-index: 10;
                }
                .ai-thumb-card:hover .ai-card-remove { display: block; }

                .ai-badge-status { padding: 4px 8px; border-radius: 4px; font-weight: bold; }
                .ai-status-waiting { background: #e6f4ea; color: #137333; border: 1px solid #ceebd6; }
                .ai-status-processing { background: #eef5fc; color: #2271b1; border: 1px solid #b8d5ee; }
                .ai-status-error { background: #fbeae1; color: #d63638; border: 1px solid #f5c2c2; }
                .ai-error-text { display: block; color: #b32d2e; font-size: 11px; margin-top: 4px; font-weight: normal; max-width: 300px; word-break: break-word; }

                .button.ai-btn-delete {
                    color: #d63638 !important;
                    border-color: #d63638 !important;
                    background: #fff !important;
                    transition: all 0.2s ease;
                }
                .button.ai-btn-delete:hover {
                    background: #d63638 !important;
                    color: #fff !important;
                }
            </style>

            <div id="ai_workspace">
                <p style="margin-bottom: 15px; color: #555; text-align: center; font-style: italic;">
                    Zdjęcie główne oraz zdjęcia w niebieskiej strefie zostaną przeanalizowane przez AI. Zdjęcia w dolnej sekcji trafią wyłącznie do galerii WooCommerce i zostaną pominięte przez AI.
                </p>

                <div class="ai-upload-grid-container">
                    <div>
                        <span class="ai-zone-title title-main">★ ZDJĘCIE GŁÓWNE</span>
                        <div class="ai-zone-box zone-main" id="zone_main" data-zone="main"></div>
                    </div>

                    <div>
                        <span class="ai-zone-title title-ai">+ DODATKOWE DO AI </span>
                        <div class="ai-zone-box zone-ai" id="zone_ai" data-zone="ai" style="margin-bottom: 15px;"></div>

                        <span class="ai-zone-title title-gallery">📁 POZOSTAŁE ZDJĘCIA </span>
                        <div class="ai-zone-box zone-gallery" id="zone_gallery" data-zone="gallery"></div>
                    </div>
                </div>

                <div style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; text-align: center;">
                    <h2>Krok 2: Wyślij gotowy produkt do AI</h2>
                    <button type="button" class="button button-primary button-large" id="ai_submit_product_btn" style="background: #9b51e0; border-color: #883cd1; font-size: 16px; padding: 6px 35px; height: auto;">
                        Dodaj produkt do wygenerowania opisu
                    </button>
                </div>
            </div>
            
            <div id="ai_upload_status" style="margin-top: 15px; font-weight: bold; text-align: center; color: #555;"></div>
        </div>

        <h2 style="margin-top: 40px;">Kolejka przetwarzania</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 100px;">Miniaturka</th>
                    <th>Tymczasowy tytuł / ID</th>
                    <th>Liczba zdjęć (AI/łącznie)</th>
                    <th>Status</th>
                    <th style="width: 150px;">Akcje</th>
                </tr>
            </thead>
            <tbody id="ai_processing_table">
                <tr><td colspan="5">Ładowanie kolejki przetwarzania...</td></tr>
            </tbody>
        </table>
    </div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var photoState = {
        main: null,
        ai: [],
        gallery: []
    };

    var draggedImgId = null;

    function getAllPhotos() {
        var list = [];
        if (photoState.main) list.push(photoState.main);
        return list.concat(photoState.ai, photoState.gallery);
    }

    function removePhotoFromState(id) {
        if (photoState.main && photoState.main.id == id) {
            photoState.main = null;
        }
        photoState.ai = photoState.ai.filter(function(item) { return item.id != id; });
        photoState.gallery = photoState.gallery.filter(function(item) { return item.id != id; });
    }

    function addPhotosToState(newAttachments) {
        newAttachments.forEach(function(att) {
            var exists = getAllPhotos().some(function(item) { return item.id == att.id; });
            if (exists) return;

            var item = { id: att.id, url: att.url };

            if (!photoState.main) {
                photoState.main = item;
            } else if (photoState.ai.length < 2) {
                photoState.ai.push(item);
            } else {
                photoState.gallery.push(item);
            }
        });
        renderUI();
    }

    function renderUI() {
        var all = getAllPhotos();
        
        if (all.length === 0) {
            $('#ai_workspace').hide();
            return;
        }
        $('#ai_workspace').show();

        var mainBox = $('#zone_main').empty();
        if (photoState.main) {
            mainBox.append(createCardHtml(photoState.main));
        } else {
            mainBox.html('<span style="color:#aaa; font-size:11px; margin:auto;">Przeciągnij tu zdjęcie główne (wymagane)</span>');
        }

        var aiBox = $('#zone_ai').empty();
        if (photoState.ai.length > 0) {
            photoState.ai.forEach(function(item) { aiBox.append(createCardHtml(item)); });
        } else {
            aiBox.html('<span style="color:#aaa; font-size:11px; margin:auto;">Przeciągnij tu dodatkowe zdjęcia dla AI</span>');
        }

        var galleryBox = $('#zone_gallery').empty();
        if (photoState.gallery.length > 0) {
            photoState.gallery.forEach(function(item) { galleryBox.append(createCardHtml(item)); });
        } else {
            galleryBox.html('<span style="color:#aaa; font-size:11px; margin:auto;">Pozostałe zdjęcia galerii</span>');
        }
    }

    function createCardHtml(item) {
        return '<div class="ai-thumb-card" draggable="true" data-id="' + item.id + '">' +
                   '<div class="ai-card-remove" title="Usuń">&times;</div>' +
                   '<img src="' + item.url + '">' +
               '</div>';
    }

    $(document).on('dragstart', '.ai-thumb-card', function(e) {
        draggedImgId = $(this).data('id');
        e.originalEvent.dataTransfer.setData('text/plain', draggedImgId);
    });

    $(document).on('dragover', '.ai-zone-box', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    });

    $(document).on('dragleave', '.ai-zone-box', function(e) {
        $(this).removeClass('drag-over');
    });

    $(document).on('drop', '.ai-zone-box', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        var targetZone = $(this).data('zone');

        if (!draggedImgId) return;

        var all = getAllPhotos();
        var movedItem = all.find(function(item) { return item.id == draggedImgId; });
        if (!movedItem) return;

        if (targetZone === 'main') {
            removePhotoFromState(movedItem.id);
            if (photoState.main) {
                photoState.gallery.unshift(photoState.main);
            }
            photoState.main = movedItem;

        } else if (targetZone === 'ai') {
            if (photoState.ai.some(function(item) { return item.id == movedItem.id; })) return;
            removePhotoFromState(movedItem.id);
            photoState.ai.push(movedItem);

        } else if (targetZone === 'gallery') {
            if (photoState.gallery.some(function(item) { return item.id == movedItem.id; })) return;
            removePhotoFromState(movedItem.id);
            photoState.gallery.push(movedItem);
        }

        draggedImgId = null;
        renderUI();
    });

    $(document).on('click', '.ai-card-remove', function(e) {
        e.stopPropagation();
        var id = $(this).parent().data('id');
        removePhotoFromState(id);

        if (!photoState.main) {
            if (photoState.ai.length > 0) {
                photoState.main = photoState.ai.shift();
            } else if (photoState.gallery.length > 0) {
                photoState.main = photoState.gallery.shift();
            }
        }

        renderUI();
    });

    $('#ai_select_photos_btn').on('click', function(e) {
        e.preventDefault();
        var mediaUploader = wp.media({
            title: 'Wybierz zdjęcia produktu',
            button: { text: 'Dodaj do zestawu' },
            library: { type: 'image' },
            multiple: true
        });

        mediaUploader.on('select', function() {
            var attachments = mediaUploader.state().get('selection').toJSON();
            addPhotosToState(attachments);
        });
        mediaUploader.open();
    });

    $('#ai_select_folder_btn').on('click', function() {
        $('#ai_folder_input').click();
    });

    $('#ai_folder_input').on('change', function(e) {
        var files = e.target.files;
        if (!files.length) return;

        $('#ai_upload_status').html('<span style="color: #2271b1;">Wgrywanie plików z folderu do biblioteki...</span>');

        var formData = new FormData();
        var imageFilesCount = 0;

        $.each(files, function(i, file) {
            if (file.type.startsWith('image/')) {
                formData.append('async_folder_images[]', file);
                imageFilesCount++;
            }
        });

        if (imageFilesCount === 0) {
            $('#ai_upload_status').html('<span style="color: red;">W folderze nie znaleziono obrazów.</span>');
            return;
        }

        formData.append('action', 'ai_upload_folder_images');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    addPhotosToState(response.data);
                    $('#ai_upload_status').html('<span style="color: green;">Pomyślnie wgrano ' + response.data.length + ' zdjęć z folderu.</span>');
                } else {
                    $('#ai_upload_status').html('<span style="color: red;">Błąd podczas wgrywania folderu.</span>');
                }
            }
        });
    });

    $('#ai_submit_product_btn').on('click', function(e) {
        e.preventDefault();
        
        if (!photoState.main) {
            alert('Musisz wybrać zdjęcie główne!');
            return;
        }

        var aiIds = [photoState.main.id];
        photoState.ai.forEach(function(item) { aiIds.push(item.id); });

        var galleryIds = photoState.gallery.map(function(item) { return item.id; });

        $('#ai_upload_status').html('<span style="color: #9b51e0;">Tworzenie produktu i wysyłanie do kolejki AI...</span>');

        $.post(ajaxurl, {
            action: 'ai_create_bulk_products',
            ai_image_ids: aiIds,
            gallery_image_ids: galleryIds
        }, function(response) {
            if(response.success) {
                $('#ai_upload_status').html('<span style="color: green;">Produkt pomyślnie dodany do kolejki AI!</span>');
                photoState = { main: null, ai: [], gallery: [] };
                renderUI();
                loadAiQueue();
            } else {
                $('#ai_upload_status').text('❌ Wystąpił błąd podczas zapisu.');
            }
        });
    });

    $('#ai_processing_table').on('click', '.ai-row-action-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var actionType = btn.data('action');
        var productId = btn.data('id');

        btn.prop('disabled', true).text('...');

        $.post(ajaxurl, {
            action: 'ai_handle_row_action',
            sub_action: actionType,
            product_id: productId
        }, function(response) {
            if(response.success) {
                loadAiQueue();
            } else {
                alert('Operacja nie powiodła się.');
                btn.prop('disabled', false).text(actionType === 'retry' ? 'Ponów' : 'Usuń');
            }
        });
    });

    function loadAiQueue() {
        $.post(ajaxurl, { action: 'ai_get_processing_queue' }, function(response) {
            if(response.success && response.data.length > 0) {
                var html = '';
                response.data.forEach(function(item) {
                    var statusHtml = '';
                    var actionsHtml = '';

                    if(item.status === 'error' || item.status === 'broken') {
                        statusHtml = '<span class="ai-badge-status ai-status-error">Błąd podczas generowania</span>' +
                                     '<span class="ai-error-text"> Powód: ' + (item.error_msg || 'Nieznany błąd') + '</span>';
                        
                        actionsHtml = '<button class="button button-secondary ai-row-action-btn" data-action="retry" data-id="' + item.id + '" style="margin-right:5px; color: green; border-color:green;">Ponów</button>' +
                                      '<button class="button button-secondary ai-btn-delete ai-row-action-btn" data-action="delete" data-id="' + item.id + '">Usuń</button>';
                    } else if(item.status === 'processing') {
                        statusHtml = '<span class="ai-badge-status ai-status-processing">Trwa przetwarzanie...</span>';
                        actionsHtml = '<span style="color:#999; font-style:italic;">Brak (w procesie)</span>';
                    } else {
                        statusHtml = '<span class="ai-badge-status ai-status-waiting">Oczekuje</span>';
                        actionsHtml = '<button class="button button-secondary ai-btn-delete ai-row-action-btn" data-action="delete" data-id="' + item.id + '">Usuń</button>';
                    }

                    html += '<tr>' +
                            '<td><img src="' + item.thumb + '" style="max-width: 60px; height: auto; border-radius: 4px;"></td>' +
                            '<td><strong>' + item.title + '</strong> (ID: ' + item.id + ')</td>' +
                            '<td><strong>' + item.ai_count + ' / ' + item.total_count + '</strong></td>' +
                            '<td>' + statusHtml + '</td>' +
                            '<td>' + actionsHtml + '</td>' +
                            '</tr>';
                });
                $('#ai_processing_table').html(html);
            } else {
                $('#ai_processing_table').html('<tr><td colspan="5">Obecnie żadne produkty nie czekają w kolejce. Wszystko przetworzone!</td></tr>');
            }
        });
    }
    loadAiQueue();
    setInterval(loadAiQueue, 5000);
});
</script>
    <?php
}

// Handler AJAX do masowego wgrywania plików z folderu dysku
add_action( 'wp_ajax_ai_upload_folder_images', 'ai_upload_folder_images_callback' );
function ai_upload_folder_images_callback() {
    if ( empty($_FILES['async_folder_images']) ) {
        wp_send_json_error('Brak plików.');
    }

    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

    $files = $_FILES['async_folder_images'];
    $uploaded_attachments = [];

    foreach ($files['name'] as $key => $value) {
        if ($files['name'][$key]) {
            $file = array(
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error'    => $files['error'][$key],
                'size'     => $files['size'][$key]
            );

            $_FILES['single_folder_file'] = $file;
            $attachment_id = media_handle_upload('single_folder_file', 0);

            if (!is_wp_error($attachment_id)) {
                $uploaded_attachments[] = [
                    'id'  => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id)
                ];
            }
        }
    }

    wp_send_json_success($uploaded_attachments);
}

/**
 * Status automatyzacji
 */
// Obsługa AJAX - Tworzenie wersji roboczej produktu ze statusem "waiting"
add_action( 'wp_ajax_ai_create_bulk_products', 'ai_create_bulk_products_callback' );
function ai_create_bulk_products_callback() {
    $ai_image_ids      = isset($_POST['ai_image_ids']) ? array_map('intval', $_POST['ai_image_ids']) : array();
    $gallery_image_ids = isset($_POST['gallery_image_ids']) ? array_map('intval', $_POST['gallery_image_ids']) : array();

    if (empty($ai_image_ids)) {
        wp_send_json_error('Zdjęcie główne jest wymagane.');
    }

    $product = new WC_Product_Simple();
    $product->set_name( 'Produkt AI (' . date('Y-m-d H:i:s') . ')' );
    $product->set_status( 'draft' );

    $main_image_id = $ai_image_ids[0];
    $product->set_image_id( $main_image_id );

    $ai_extra_ids   = array_slice($ai_image_ids, 1); 
    $sent_to_ai_ids = $ai_image_ids;

    $all_gallery_ids = array_unique(array_merge($ai_extra_ids, $gallery_image_ids));
    $product->set_gallery_image_ids($all_gallery_ids);

    $product->update_meta_data( '_ai_automation_status', 'waiting' );
    $product->update_meta_data( '_ai_sent_image_ids', $sent_to_ai_ids );
    $product->update_meta_data( '_ai_sent_image_count', count($sent_to_ai_ids) );

    $product_id = $product->save();

    if ($product_id) {
        wp_send_json_success( array('product_id' => $product_id) );
    } else {
        wp_send_json_error( 'Błąd zapisu produktu.' );
    }
}

// Silnik PHP: Pobieranie danych do tabeli na podstawie pola _ai_automation_status
add_action( 'wp_ajax_ai_get_processing_queue', 'ai_get_processing_queue_callback' );
function ai_get_processing_queue_callback() {
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'draft',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_ai_automation_status',
                'value'   => array( 'waiting', 'processing', 'error', 'broken' ),
                'compare' => 'IN'
            )
        )
    );

    $loop = new WP_Query( $args );
    $data = array();

    while ( $loop->have_posts() ) {
        $loop->the_post();
        $product_id = get_the_ID();
        $product    = wc_get_product( $product_id );

        $status    = $product->get_meta( '_ai_automation_status' );
        $error_msg = $product->get_meta( '_ai_last_error_message' );
        
        $sent_ids = $product->get_meta( '_ai_sent_image_ids' );
        $ai_count = is_array($sent_ids) ? count($sent_ids) : 1;

        $main_img_id = $product->get_image_id();
        $gallery_ids = $product->get_gallery_image_ids();
        if (!is_array($gallery_ids)) {
            $gallery_ids = array();
        }

        $all_unique_ids = array_unique(array_filter(array_merge(
            $main_img_id ? array($main_img_id) : array(),
            $gallery_ids
        )));
        
        $total_count = count($all_unique_ids);
        $thumb_url   = $main_img_id ? wp_get_attachment_thumb_url( $main_img_id ) : wc_placeholder_img_src();

        $data[] = array(
            'id'          => $product_id,
            'title'       => get_the_title(),
            'thumb'       => $thumb_url,
            'status'      => $status ? $status : 'waiting',
            'error_msg'   => $error_msg ? $error_msg : '',
            'ai_count'    => $ai_count,
            'total_count' => $total_count
        );
    }
    wp_reset_postdata();

    wp_send_json_success( $data );
}

// Obsługa akcji "USUŃ" lub "PONÓW" z poziomu tabeli
add_action( 'wp_ajax_ai_handle_row_action', 'ai_handle_row_action_callback' );
function ai_handle_row_action_callback() {
    $sub_action = isset($_POST['sub_action']) ? sanitize_text_field($_POST['sub_action']) : '';
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ( !$product_id || !current_user_can('manage_options') ) {
        wp_send_json_error();
    }

    if ( $sub_action === 'delete' ) {
        wp_delete_post( $product_id, true );
        wp_send_json_success();
    } elseif ( $sub_action === 'retry' ) {
        update_post_meta( $product_id, '_ai_automation_status', 'waiting' );
        delete_post_meta( $product_id, '_ai_last_error_message' );
        wp_send_json_success();
    }

    wp_send_json_error();
}

// Ukrywanie produktów ze statusami AI z domyślnej listy produktów WooCommerce
add_action( 'pre_get_posts', 'ai_hide_drafts_from_standard_woocommerce' );
function ai_hide_drafts_from_standard_woocommerce( $query ) {
    if ( is_admin() && $query->is_main_query() && $query->get('post_type') === 'product' ) {
        if ( !isset($_GET['post_status']) || $_GET['post_status'] !== 'ai_queue' ) {
            $meta_query = (array) $query->get('meta_query');
            $meta_query[] = [
                'key'     => '_ai_automation_status',
                'compare' => 'NOT EXISTS'
            ];
            $query->set( 'meta_query', $meta_query );
        }
    }
}

// Automatyczne czyszczenie flag po opublikowaniu
add_action( 'transition_post_status', 'ai_auto_clear_flag_on_status_change', 10, 3 );
function ai_auto_clear_flag_on_status_change( $new_status, $old_status, $post ) {
    if ( $post->post_type === 'product' && $new_status === 'pending' ) {
        delete_post_meta( $post->ID, '_ai_automation_status' );
        delete_post_meta( $post->ID, '_ai_last_error_message' );
    }
}

/**
 * Dedykowane punkty końcowe
 */
// Rejestracja pól w "Ustawienia -> Ogólne" (widoczne tylko dla administratora)
add_action( 'admin_init', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    register_setting( 'general', 'ai_prompt_vision', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    register_setting( 'general', 'ai_prompt_llm', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ) );

    add_settings_field( 'ai_prompt_vision', 'Prompt Vision (generowanie opisów)', function() {
        echo '<textarea name="ai_prompt_vision" rows="4" class="large-text">' . esc_textarea( get_option('ai_prompt_vision') ) . '</textarea>';
    }, 'general' );

    add_settings_field( 'ai_prompt_llm', 'Prompt LLM (generowanie opisów)', function() {
        echo '<textarea name="ai_prompt_llm" rows="8" class="large-text">' . esc_textarea( get_option('ai_prompt_llm') ) . '</textarea>';
    }, 'general' );
});

// Endpoint REST API czytający prompty bezpośrednio z bazy
add_action( 'rest_api_init', function () {
    register_rest_route( 'system-ai/v1', '/prompts', array(
        'methods'  => 'GET',
        'callback' => function() {
            return array(
                'prompt_vision' => get_option( 'ai_prompt_vision', '' ),
                'prompt_llm'    => get_option( 'ai_prompt_llm', '' )
            );
        },
        'permission_callback' => '__return_true'
    ) );
} );

// Rozszerzenie natywnego REST API WooCommerce o możliwość filtrowania po meta_key i meta_value
add_filter( 'woocommerce_rest_product_object_query', function( $args, $request ) {
    if ( ! empty( $request['meta_key'] ) ) {
        $args['meta_key'] = sanitize_text_field( $request['meta_key'] );
    }
    if ( ! empty( $request['meta_value'] ) ) {
        $args['meta_value'] = sanitize_text_field( $request['meta_value'] );
    }
    return $args;
}, 10, 2 );

/**
 * Alert błędu potoku
 */
// Globalny Log Błędów n8n z automatyczną zmianą stanu przetwarzanego produktu
add_action('rest_api_init', function () {
    register_rest_route('v1/n8n-config', '/log-error', [
        'methods'             => 'POST',
        'callback'            => 'save_n8n_error_log',
        'permission_callback' => '__return_true'
    ]);
});

function save_n8n_error_log($request) {
    $params = $request->get_json_params();
    
    $workflow_name = sanitize_text_field($params['workflow_name'] ?? 'Nieznany potok');
    $node_name     = sanitize_text_field($params['node_name'] ?? 'Nieznany węzeł');
    $error_message = sanitize_textarea_field($params['error_message'] ?? 'Brak komunikatu błędu');
    
    // Zapis logu błędu w bazie opcji dla baneru w kokpicie
    $logs = get_option('n8n_error_logs', []);
    $new_log = [
        'time'     => current_time('mysql'),
        'workflow' => $workflow_name,
        'node'     => $node_name,
        'error'    => $error_message
    ];
    
    array_unshift($logs, $new_log);
    $logs = array_slice($logs, 0, 5);
    update_option('n8n_error_logs', $logs);
    
    // Automatyczna zmiana statusu produktu, który znajdował się w stanie 'processing'
    $processing_products = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'draft',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => '_ai_automation_status',
                'value'   => 'processing',
                'compare' => '='
            ]
        ]
    ]);

    if ( ! empty( $processing_products ) ) {
        $active_product_id = $processing_products[0]->ID;
        update_post_meta( $active_product_id, '_ai_automation_status', 'error' );
        update_post_meta( $active_product_id, '_ai_last_error_message', 'Błąd potoku [' . $node_name . ']: ' . $error_message );
    }

    return new WP_REST_Response(['status' => 'success'], 200);
}

// Wyświetlanie powiadomienia w kokpicie WordPressa
add_action('admin_notices', 'render_n8n_error_notices');

function render_n8n_error_notices() {
    $logs = get_option('n8n_error_logs', []);
    if (empty($logs)) return;
    
    $latest_error = $logs[0];
    ?>
    <div id="n8n-error-notice" class="notice notice-error is-dismissible" style="border-left-color: #d63638; padding: 12px; margin-top: 20px;">
        <p style="margin: 0 0 5px 0; font-size: 14px; font-weight: bold; color: #d63638;">
            Wykryto krytyczny błąd w automatyzacji AI (n8n)!
        </p>
        <p style="margin: 0 0 8px 0;">
            <strong>Potok:</strong> <?php echo esc_html($latest_error['workflow']); ?> | 
            <strong>Nod:</strong> <code style="background: #f0f0f1; padding: 3px 6px; font-size: 12px;"><?php echo esc_html($latest_error['node']); ?></code> | 
            <strong>Czas:</strong> <?php echo esc_html($latest_error['time']); ?>
        </p>
        <p style="background: #fafafa; border-left: 4px solid #d63638; padding: 8px; margin: 5px 0 0 0; font-family: monospace; font-size: 13px; color: #50575e;">
            <strong>Powód:</strong> <?php echo esc_html($latest_error['error']); ?>
        </p>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $(document).on('click', '#n8n-error-notice .notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'dismiss_n8n_error_notice',
                nonce: '<?php echo wp_create_nonce("dismiss_n8n_error_nonce"); ?>'
            });
        });
    });
    </script>
    <?php
}

// Obsługa AJAX – czyszczenie baneru z bazy po zamknięciu
add_action('wp_ajax_dismiss_n8n_error_notice', 'handle_dismiss_n8n_error_notice');

function handle_dismiss_n8n_error_notice() {
    check_ajax_referer('dismiss_n8n_error_nonce', 'nonce');
    delete_option('n8n_error_logs');
    wp_send_json_success();
}
