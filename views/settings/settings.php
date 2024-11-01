<?php
global $screen_layout_columns;
$screen_layout_columns = 2;
?>
<div class="wrap">
    <?php screen_icon( 'options-general' ); ?>
    <h2>AutoLead Options</h2>
    <?php settings_errors(); ?>
    <form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
        <?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', FALSE ); ?>
        <?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', FALSE ); ?>
        <?php settings_fields( 'autolead-options-group' ); ?>
        <div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
            <div id="side-info-column" class="inner-sidebar">
                <?php do_meta_boxes( $this->settings_page, 'side', '' ); ?>
            </div>
            <div id="post-body" class="has-sidebar">
                <div id="post-body-content" class="has-sidebar-content">
                    <?php do_meta_boxes( $this->settings_page, 'normal', '' ); ?>
                </div>
            </div>
            <br class="clear"/>
        </div>
        <?php submit_button(); ?>
    </form>
</div>
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
        postboxes.add_postbox_toggles('<?php echo $this->settings_page; ?>');
    });
</script>