<?php $list_css_rules = get_post_meta( $post->ID, '_autolead_list_css_rules', TRUE ); ?>
<table class="form-table autolead-form">
    <tbody>
        <tr valign="top">
            <th scope="row">List Css Rules</th>
            <td>
                <textarea name="autolead_list_css_rules" rows="10"><?php echo $list_css_rules; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>