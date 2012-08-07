<?php
/**
 * CSV Import admin pages.
 * @since 2.1
 */
class WPBDP_CSVImportAdmin {

    public static function admin_menu_cb() {
        $instance = new WPBDP_CSVImportAdmin();
        $instance->dispatch();
    }

    public function __construct() {
        $this->admin = wpbdp()->admin;
    }

    public function dispatch() {
        $action = wpbdp_getv($_REQUEST, 'action');
        $api = wpbdp_formfields_api();

        switch ($action) {
            case 'example-csv':
                $this->example_csv();
                break;
            case 'do-import':
                $this->import();
                break;
            default:
                $this->import_settings();
                break;
        }
    }

    private function example_data_for_field($field=nulll, $shortname=null) {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ($field) {
            if ($field->association == 'title') {
                return sprintf(_x('Business %s', 'admin csv-import', 'WPBDM'), $letters[rand(0,strlen($letters)-1)]);
            } elseif ($field->association == 'category') {
                if ( $terms = get_terms(wpbdp_categories_taxonomy(), 'number=5&hide_empty=0') ) {
                    return $terms[array_rand($terms)]->name;
                } else {
                    return '';
                }
            } elseif ($field->association == 'tags') {
                if ( $terms = get_terms(wpbdp_tags_taxonomy(), 'number=5&hide_empty=0') ) {
                    return $terms[array_rand($terms)]->name;
                } else {
                    return '';
                }                
            } elseif ($field->validator == 'URLValidator') {
                return get_site_url();
            } elseif ($field->validator == 'EmailValidator') {
                return get_option('admin_email');
            } elseif ($field->validator == 'IntegerNumberValidator') {
                return rand(0, 100);
            } elseif ($field->validator == 'DecimalNumberValidator') {
                return rand(0, 100) / 100.0;
            } elseif ($field->validator == 'DateValidator') {
                return date('d/m/Y');
            } elseif ($field->type == 'multiselect' || $field->type == 'checkbox') {
                if (isset($field->field_data['options'])) {
                    if ($options = $field->field_data['options']) {
                        return $options[array_rand($options)];
                    }
                }
                
                return '';
            }
        }

        if ($shortname == 'user') {
            $users = get_users();
            return $users[array_rand($users)]->user_login;
        }

        return _x('Whatever', 'admin csv-import', 'WPBDM');
    }

    private function example_csv() {
        echo wpbdp_admin_header(_x('Example CSV Import File', 'admin csv-import', 'WPBDM'), null, array(
            array(_x('← Return to "CSV Import"', 'admin csv-import', 'WPBDM'), esc_url(remove_query_arg('action')))
        ));

        $posts = get_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'numberposts' => 10
        ));

        //echo sprintf('<input type="button" value="%s" />', _x('Copy CSV', 'admin csv-import', 'WPBDM'));
        echo '<textarea class="wpbdp-csv-import-example" rows="30">';

        $short_names = wpbdp_formfields_api()->getShortNames();

        foreach ($short_names as $name) {
            echo $name . ',';
        }
        echo 'username';
        echo "\n";

        if (count($posts) >= 5) {
            foreach ($posts as $post) {
                foreach (array_keys($short_names) as $field_id) {
                    if ($field_value = wpbdp_get_listing_field_value($post, $field_id)) {
                        if (is_array($field_value)) {
                            if ($field_value) {
                                $values = array_values($field_value);
                                echo $values[0]->name;
                            } else {
                            }
                        } else {
                            echo str_replace(',', '', $field_value);
                        }
                    }

                    echo ',';
                }
                echo get_the_author_meta('user_login', $post->post_author);

                echo "\n";
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                foreach ($short_names as $field_id => $shortname) {
                    $field = wpbdp_formfields_api()->getField($field_id);
                    echo $this->example_data_for_field($field, $short_names);
                    echo ',';
                }

                echo $this->example_data_for_field(null, 'user');
                echo "\n";
            }
            
        }

        echo '</textarea>';

        echo wpbdp_admin_footer();
    }

    private function import_settings() {
        echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/csv-import.tpl.php');
    }

    private function import() {
        $csvfile = $_FILES['csv-file'];
        $zipfile = $_FILES['images-file'];

        if ($csvfile['error'] || !is_uploaded_file($csvfile['tmp_name'])) {
            $this->admin->messages[] = array(_x('There was an error uploading the CSV file.', 'admin csv-import', 'WPBDM'), 'error');
            return $this->import_settings();
        }

        if (strtolower(pathinfo($csvfile['name'], PATHINFO_EXTENSION)) != 'csv' &&
            $csvfile['type'] != 'text/csv') {
            $this->admin->messages[] = array(_x('The uploaded file does not look like a CSV file.', 'admin csv-import', 'WPBDM'), 'error');
            return $this->import_settings();
        }

        $formfields_api = wpbdp_formfields_api();
        $form_fields = $formfields_api->getFields();
        $shortnames = $formfields_api->getShortNames();

        $fields = array();
        foreach ($form_fields as $field)
            $fields[$shortnames[$field->id]] = $field;

        $importer = new WPBDP_CSVImporter();
        $importer->set_settings(array_merge($_POST['settings'], array('test-import' => isset($_POST['test-import']) ? true : false)));
        $importer->set_fields($fields);
        $importer->import($csvfile['tmp_name'], $zipfile['tmp_name']);

        if ($importer->in_test_mode())
            $this->admin->messages[] = array(_x('* Import is in test mode. Nothing was actually inserted into the database. *', 'admin csv-import', 'WPBDM'), 'error');

        if ($importer->rejected_rows)
            $this->admin->messages[] = _x('Import was completed but some rows were rejected.', 'admin csv-import', 'WPBDM');
        else
            $this->admin->messages[] = _x('Import was completed successfully.', 'admin csv-import', 'WPBDM');

        echo wpbdp_admin_header();
        echo wpbdp_admin_notices();

        echo '<h3>' . _x('Import Summary', 'admin csv-import', 'WPBDM') . '</h3>';
        echo '<dl>';
        echo '<dt>' . _x('Correctly imported rows:', 'admin csv-import', 'WPBDM') . '</dt>';
        echo '<dd>' . count($importer->imported_rows) . '</dd>';
        echo '<dt>' . _x('Rejected rows:', 'admin csv-import', 'WPBDM') . '</dt>';
        echo '<dd>' . count($importer->rejected_rows) . '</dd>';
        echo '</dl>';

        if ($importer->rejected_rows) {
            echo '<h3>' . _x('Rejected Rows', 'admin csv-import', 'WPBDM') . '</h3>';
            echo '<table class="wpbdp-csv-import-results wp-list-table widefat">';
            echo '<thead><tr>';
            echo '<th class="line-no">' . _x('Line #', 'admin csv-import', 'WPBDM') . '</th>';
            echo '<th class="line">' . _x('Line', 'admin csv-import', 'WPBDM') . '</th>';
            echo '<th class="error">' . _x('Error', 'admin csv-import', 'WPBDM') . '</th>';
            echo '</tr></thead>';

            echo '<tbody>';

            foreach ($importer->rejected_rows as $row) {
                foreach ($row['errors'] as $i => $error) {
                    echo sprintf('<tr class="%s">', $i % 2 == 0 ? 'alternate' : '');
                    echo '<td class="line-no">' . $row['line'] . '</td>';
                    echo '<td class="line">' . substr($importer->csv[$row['line'] - 1], 0, 60) . '...</td>';
                    echo '<td class="error">' . $error . '</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
        }

        if ($importer->warnings > 0) {
            echo '<h3>' . _x('Import warnings (not critical)', 'admin csv-import', 'WPBDM') . '</h3>';
            echo '<table class="wpbdp-csv-import-warnings wp-list-table widefat">';
            echo '<thead><tr>';
            echo '<th class="line-no">' . _x('Line #', 'admin csv-import', 'WPBDM') . '</th>';
            echo '<th class="line">' . _x('Line', 'admin csv-import', 'WPBDM') . '</th>';
            echo '<th class="error">' . _x('Warning', 'admin csv-import', 'WPBDM') . '</th>';
            echo '</tr></thead>';

            echo '<tbody>';        
            foreach ($importer->imported_rows as $row) {
                if (!isset($row['warnings']))
                    continue;

                foreach ($row['warnings'] as $i => $warning) {
                    echo sprintf('<tr class="%s">', $i % 2 == 0 ? 'alternate' : '');
                    echo '<td class="line-no">' . $row['line'] . '</td>';
                    echo '<td class="line">' . substr($importer->csv[$row['line'] - 1], 0, 60) . '...</td>';
                    echo '<td class="error">' . $warning . '</td>';
                    echo '</tr>';
                }

            }
            echo '</tbody>';
            echo '</table>';
        }

        echo wpbdp_admin_footer();
    }

}


require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

/**
 * CSV import class.
 * @since 2.1
 */
class WPBDP_CSVImporter {

    private $settings = array(
        'allow-partial-imports' => true,

        'csv-file-separator' => ',',
        'images-separator' => ';',
        'create-missing-categories' => true,

        'assign-listings-to-user' => true,
        'default-user' => '0',

        'test-import' => false
    );

    private $fields = array();
    
    public $csv = array();
    private $header = array();
    private $data = array();

    private $imagesdir = null;

    public $rows = array(); /* valid rows */
    public $imported_rows = array();
    public $rejected_rows = array();
    public $warnings = 0;


    public function __construct() { }

    public function set_fields($fields) {
        $this->fields = $fields;
    }

    public function set_settings($settings=array()) {
        $this->settings = array_merge($this->settings, $settings);
        $this->settings['allow-partial-imports'] = (boolean) $this->settings['allow-partial-imports'];        
        $this->settings['create-missing-categories'] = (boolean) $this->settings['create-missing-categories'];
        $this->settings['assign-listings-to-user'] = (boolean) $this->settings['assign-listings-to-user'];
        $this->settings['default-user'] = intval($this->settings['default-user']);
    }

    public function in_test_mode() {
        return $this->settings['test-import'] == true;
    }

    public function reset() {
        $this->csv = array();
        $this->header = array();
        $this->data = array();

        $this->rows = array();
        $this->imported_rows = array();
        $this->rejected_rows = array();
        $this->warnings = 0;

        $this->imagesdir = null;
    }

    public function import($csv_file, $zipfile) {
        $this->reset();
        $this->extract_data($csv_file);
        $this->extract_images($zipfile);

        foreach ($this->rows as $row) {
            if ($this->import_row($row['data'], $errors, $warnings)) {
                if ($warnings) {
                    $this->warnings += count($warnings);
                    $row['warnings'] = $warnings;
                }

                $this->imported_rows[] = $row;
            } else {
                $row['errors'] = $errors;
                $this->rejected_rows[] = $row;
            }
        }

        // delete $imagesdir
        if ($this->imagesdir)
            $this->remove_directory($this->imagesdir);
    }

    private function process_line($line) {
        $row = str_getcsv($line, $this->settings['csv-file-separator']);

        if (count($row) > count($this->header)) {
            return false; // row has more columns than the header
        }

        if (count($row) < count($this->header)) {
            $row = array_merge($row, array_fill(0, count($this->header) - count($row), null));            
        }

        return $row;
    }

    private function extract_data($csv_file) {
        $this->csv = explode("\n", str_replace(array("\r\n", "\r"), "\n", file_get_contents($csv_file)));
        array_map('rtrim', $this->csv);

        foreach ($this->csv as $n => $line) {
            $line = trim($line);

            if ($line) {
                if (!$this->header) {
                    $this->header = str_getcsv($line, $this->settings['csv-file-separator']);
                } else {
                    if ($row = $this->process_line($line)) {
                        $this->rows[] = array('line' => $n + 1, 'data' => $row, 'error' => false);
                    } else {
                        $this->rejected_rows[] = array('line' => $n + 1, 'data' => $row, 'error' => _x('Malformed row (too many columns)', 'admin csv-import', 'WPBDM') );
                    }
                }
            }
        }
    }

    private function extract_images($zipfile) {
        $dir = trailingslashit(trailingslashit(sys_get_temp_dir()) . 'wpbdp_' . time());

        require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');

        $zip = new PclZip($zipfile);
        if ($files = $zip->extract(PCLZIP_OPT_PATH, $dir, PCLZIP_OPT_REMOVE_ALL_PATH)) {
            $this->imagesdir = $dir;
            return true;
        }

        return false;
    }

    private function import_row($data, &$errors=null, &$warnings=null) {
        $errors = array();
        $warnings = array();

        $listing_username = null;

        $listing = array('fields' => array(), 'images' => array());

        $listing_images = array();
        $listing_fields = array();

        foreach ($this->header as $i => $header_name) {
            if ( ($header_name == 'image' || $header_name == 'images') ) {
                if ( !empty($data[$i]) ) {
                    if (strpos($data[$i], $this->settings['images-separator']) !== false) {
                        foreach (explode($this->settings['images-separator'], $data[$i]) as $image) {
                            $listing_images[] = trim($image);
                        }
                    } else {
                        $listing_images[] = trim($data[$i]);
                    }
                }

                continue;
            }

            if ($header_name == 'username') {
                $listing_username = $data[$i];
                continue;
            }

            if (!array_key_exists($header_name, $this->fields)) {
                $warnings[] = sprintf(_x('Ignoring unknown field "%s"', 'admin csv-import', 'WPBDM'), $header_name);
                continue;
            }            

            $field = $this->fields[$header_name];

            if ($field->association == 'category' && !empty($data[$i])) {
                if ($term = term_exists($data[$i], wpbdp_categories_taxonomy())) {
                    $listing_fields[$field->id][] = $term['term_id'];
                } else {
                    if ($this->settings['create-missing-categories']) {
                        if ($newterm = wp_insert_term($data[$i], wpbdp_categories_taxonomy())) {
                            $listing_fields[$field->id][] = $newterm['term_id'];
                        } else {
                            $errors[] = sprintf(_x('Could not create listing category "%s"', 'admin csv-import', 'WPBDM'), $data[$i]);
                            return false;
                        }
                        
                    } else {
                        $errors[] = sprintf(_x('Listing category "%s" does not exist', 'admin csv-import', 'WPBDM'), $data[$i]);
                        return false;
                    }
                }
            } elseif ($field->association == 'tags') {
                $listing_fields[$field->id][] = $data[$i];
            } else {
                $listing_fields[$field->id] = $data[$i];
            }
        }

        if ($listing_images) {
            if (!$this->imagesdir) {
                $errors[] = _x('Images were specified but no image file was uploaded.', 'admin csv-import', 'WPBDM');
                return false;
            }

            foreach ($listing_images as $filename) {
                if (file_exists($this->imagesdir . $filename)) {
                    $filepath = $this->imagesdir . $filename;

                    $file = array('name' => basename($filepath),
                                  'tmp_name' => $filepath,
                                  'error' => 0,
                                  'size' => filesize($filepath)
                    );

                    copy($filepath, $filepath . '.backup'); // make a file backup becase wp_handle_sideload() moves the original file and it may be needed for other listings
                    $wp_image = wp_handle_sideload($file, array('test_form' => FALSE));
                    rename($filepath . '.backup', $filepath);

                    if (!isset($wp_image['error'])) {
                        if ($attachment_id = wp_insert_attachment(array(
                                'post_mime_type' => $wp_image['type'],
                                'post_title' => preg_replace('/\.[^.]+$/', '', basename($wp_image['file'])),
                                'post_content' => '',
                                'post_status' => 'inherit'
                            ), $wp_image['file'])) {

                            $attach_data = wp_generate_attachment_metadata($attachment_id, $wp_image['file']);
                            wp_update_attachment_metadata($attachment_id, $attach_data);

                            $listing['images'][] = $attachment_id;

                        } else {
                            $errors[] = sprintf(_x('Image file "%s" could not be inserted.', 'admin csv-import', 'WPBDM'), $filename);
                            return false;
                        }
                    } else {
                        $errors[] = sprintf(_x('Image file "%s" could not be uploaded.', 'admin csv-import', 'WPBDM'), $filename);
                        return false;
                    }
                } else {
                    $errors[] = sprintf(_x('Referenced image file "%s" was not found inside ZIP file.', 'admin csv-import'. 'WPBDM'), $filename);
                    return false;
                }
            }
        }

        $listing['fields'] = $listing_fields;

        if ($this->settings['test-import'])
            return true;
        $listing_id = wpbdp_listings_api()->add_listing($listing);

        if ($this->settings['assign-listings-to-user']) {
            if ($listing_username) {
                if ($user = get_user_by('login', $listing_username))
                    wp_update_post(array('ID' => $listing_id, 'post_author' => $user->ID));
            } else {
                if ($this->settings['default-user'])
                    wp_update_post(array('ID' => $listing_id, 'post_author' => $this->settings['default-user']));
            }
        }

        return $listing_id > 0;
    }

    private function remove_directory($dir) {
        foreach (scandir($dir) as $file) {
            if ($file == '.' || $file == '..')  continue;

            if (is_dir($dir . $file)) {
                $this->remove_directory($dir . $file);
                rmdir($dir.  $file);
            } else {
                unlink($dir . $file);
            }
        }

        rmdir($dir);

        $this->imagesdir = null;
    }

 }