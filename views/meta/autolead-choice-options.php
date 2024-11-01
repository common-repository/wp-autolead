<?php 
$choice_active_button_css_rules = get_post_meta( $post->ID, '_autolead_choice_active_button_css_rules', TRUE ); 
$choice_inactive_button_css_rules = get_post_meta( $post->ID, '_autolead_choice_inactive_button_css_rules', TRUE ); 
?>
<table class="form-table autolead-form">
    <tbody>
        <tr valign="top">
            <th scope="row">Active Choice Button Css Rules</th>
            <td>
                <textarea name="autolead_choice_active_button_css_rules" rows="10"><?php echo $choice_active_button_css_rules; ?></textarea>
            </td>
        </tr>
        <tr>
            <th scope="row">Inactive Choice Button Css Rules</th>
            <td><textarea name="autolead_choice_inactive_button_css_rules" id="" cols="30" rows="10"><?php echo $choice_inactive_button_css_rules; ?></textarea></td>
        </tr>
    </tbody>
</table>