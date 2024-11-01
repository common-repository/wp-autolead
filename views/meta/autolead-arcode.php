<?php $ar_code = html_entity_decode( get_post_meta( $post->ID, '_autolead_arcode', TRUE ) ); ?>
<table class="form-table autolead-form">
    <tbody>
        <tr valign="top">
            <th scope="row">Put your autoresponder code here</th>
            <td><textarea name="autolead_arcode" rows="10"><?php echo $ar_code; ?></textarea></td>
        </tr>
    </tbody>
</table>