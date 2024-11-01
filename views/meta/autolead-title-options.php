<?php $title_css_rules = get_post_meta( $post->ID, '_autolead_title_css_rules', TRUE ); ?>
<table class="form-table autolead-form">
    <tbody>
        <tr valign="top">
            <th scope="row">Title Css Rules</th>
            <td>
                <textarea name="autolead_title_css_rules" rows="10"><?php echo $title_css_rules; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>