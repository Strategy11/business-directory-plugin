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

    private function example_csv() {
        echo wpbdp_admin_header(_x('Example CSV Import File', 'admin csv-import', 'WPBDM'), null, array(
            array(_x('← Return to "CSV Import"', 'admin csv-import', 'WPBDM'), esc_url(remove_query_arg('action')))
        ));

        $posts = get_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'numberposts' => 10
        ));

        echo '<textarea class="wpbdp-csv-import-example" rows="30">';

        $short_names = wpbdp_formfields_api()->getShortNames();

        foreach ($short_names as $name) {
            echo $name . ',';
        }
        echo 'user';
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
            // ...
        }

        echo '</textarea>';

        echo wpbdp_admin_footer();
    }

    private function import_settings() {
        echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/csv-import.tpl.php');
    }

    private function import() {
        $csvfile = $_FILES['csv-file'];

        if ($csvfile['error'] || !is_uploaded_file($csvfile['tmp_name'])) {
            $this->messages[] = array(_x('There was an error uploading the CSV file.', 'admin csv-import', 'WPBDM'), 'error');
            return $this->import_settings();
        }

        if (strtolower(pathinfo($csvfile['name'], PATHINFO_EXTENSION)) != 'csv' &&
            $csvfile['type'] != 'text/csv') {
            $this->messages[] = array(_x('The uploaded file does not look like a CSV file.', 'admin csv-import', 'WPBDM'), 'error');
            return $this->import_settings();        
        }

        $formfields_api = wpbdp_formfields_api();
        $form_fields = $formfields_api->getFields();
        $shortnames = $formfields_api->getShortNames();

        $fields = array();
        foreach ($form_fields as $field)
            $fields[$shortnames[$field->id]] = $field;

        $importer = new WPBDP_CSVImporter();
        $importer->set_settings($_POST['settings']);
        $importer->set_fields($fields);
        $importer->import($csvfile['tmp_name']);

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

            foreach ($importer->rejected_rows as $i => $row) {
                echo sprintf('<tr class="%s">', $i % 2 == 0 ? 'alternate' : '');
                echo '<td class="line-no">' . $row['line'] . '</td>';
                echo '<td class="line">' . substr($importer->csv[$row['line']], 0, 60) . '...</td>';
                echo '<td class="error">' . $this->import_error_msg($row['error']) . '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        }


        echo wpbdp_admin_footer();
    }

    private function import_error_msg($label) {
        switch ($label) {
            case 'malformed-row':
                return _x('Malformed row (too many columns)', 'admin csv-import', 'WPBDM');
                break;
            case 'category-not-found':
                return _x('Listing category does not exist', 'admin csv-import', 'WPBDM');
                break;
            case 'category-not-created':
                return _x('Could not create listing category', 'admin csv-import', 'WPBDM');
                break;
            default:
                return _x('Unknown error', 'admin csv-import', 'WPBDM');
                break;
        }
    }

}

/**
 * CSV import class.
 * @since 2.1
 */
class WPBDP_CSVImporter {

    private $settings = array(
        'csv-file-separator' => ',',
        'images-separator' => ';',
        'create-missing-categories' => true,

        'assign-listings-to-user' => true,
        'default-user' => '0'
    );

    private $fields = array();
    
    public $csv = array();
    private $header = array(); 
    private $data = array();    

    private $rows = array(); /* valid rows */
    public $imported_rows = array();
    public $rejected_rows = array();


    public function __construct() { }

    public function set_fields($fields) {
        $this->fields = $fields;
    }

    public function set_settings($settings=array()) {
        $this->settings = array_merge($this->settings, $settings);
        $this->settings['create-missing-categories'] = (boolean) $this->settings['create-missing-categories'];
    }

    public function reset() {
        $this->csv = array();
        $this->header = array();
        $this->data = array();

        $this->rows = array();
        $this->imported_rows = array();
        $this->rejected_rows = array();
    }

    public function import($csv_file) {
        $this->reset();
        $this->extract_data($csv_file);

        foreach ($this->rows as $row) {
            if ($this->import_row($row['data'], $error)) {
                $this->imported_rows[] = $row;
            } else {
                $row['error'] = $error;
                $this->rejected_rows[] = $row;
            }
        }

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
                        $this->rejected_rows[] = array('line' => $n + 1, 'data' => $row, 'error' => 'malformed-row');
                    }
                }
            }
        }
    }

    private function import_row($data, &$error=null) {
        $listingfields = array();

        foreach ($this->header as $i => $header_name) {
            if ($header_name == 'images' || $header_name == 'username')
                continue;

            $field = $this->fields[$header_name];

            if ($field->association == 'category') {
                if ($term = term_exists($data[$i], wpbdp_categories_taxonomy())) {
                    $listingfields[$field->id][] = $term['term_id'];
                } else {
                    if ($this->settings['create-missing-categories']) {
                        if ($newterm = wp_insert_term($data[$i], wpbdp_categories_taxonomy())) {
                            $listingfields[$field->id][] = $newterm['term_id'];
                        } else {
                            $error = 'category-not-created';
                            return false;
                        }
                        
                    } else {
                        $error = 'category-not-found';
                        return false;
                    }
                }
            } elseif ($field->association == 'tags') {
                $listingfields[$field->id][] = $data[$i];
            } else {
                $listingfields[$field->id] = $data[$i];
            }
        }

        $listings_api = wpbdp_listings_api();
        $listing_data = array('fields' => $listingfields);
        
        return $listings_api->add_listing($listing_data) > 0;
    }

 }