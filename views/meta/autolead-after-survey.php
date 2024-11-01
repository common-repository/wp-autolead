<?php
$after_survey = get_post_meta( $post->ID, '_autolead_after_survey', TRUE );
$autolead_after_content = html_entity_decode( get_post_meta( $post->ID, '_autolead_after_content', TRUE ) );
$al_redirect = get_post_meta( $post->ID, '_autolead_redirect', TRUE );
?>
<table class="form-table">
    <tbody>
        <tr valign="top">
            <th scope="row">After Survey Choices</th>
            <td>
                <select name="autolead_after_survey" class="al-context-dropdown">
                    <option value="hide"<?php selected( $after_survey, 'hide' ); ?>>Hide</option>
                    <option value="redirect"<?php selected( $after_survey, 'redirect' ); ?>>Redirect</option>
                    <option value="content"<?php selected( $after_survey, 'content' ); ?>>Display Content</option>
                </select>

                <div class="hidden context-opener" data-context="autolead_after_survey_redirect" data-group="autolead_after_survey">
                    <input name="autolead_redirect" type="text" placeholder="Please enter redirect url here" class="large-text" value="<?php echo $al_redirect; ?>" />
                </div>

                <div class="hidden context-opener" data-context="autolead_after_survey_content" data-group="autolead_after_survey">
                    <?php
                    $settings = array(
                        'textarea_name' => 'autolead_after_content',
                        'media_buttons' => true
                    );
                    wp_editor( $autolead_after_content, 'autolead_after_content', $settings );
                    ?>
                </div>

            </td>
        </tr>
    </tbody>
</table>