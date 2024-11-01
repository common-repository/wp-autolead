<?php $content_css_rules = get_post_meta( $post->ID, '_autolead_content_css_rules', TRUE ); ?>
<table class="form-table autolead-form">
    <tbody>
        <tr valign="top">
            <th scope="row">Content Box Css Rules</th>
            <td>
                <textarea name="autolead_content_css_rules" rows="10"><?php echo $content_css_rules; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>