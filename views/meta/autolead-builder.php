<?php
$builder_data = get_post_meta( $post->ID, '_autolead_builder_data', TRUE );
$builder_data = $builder_data ? html_entity_decode( $builder_data ) : FALSE;
wp_nonce_field( 'autolead-actions', 'autolead_nonce' );
?>

<div class="autolead-builder-wrapper">

    <div class="autolead-widget-wrapper">
        <?php
        if ( $builder_data )
        {
            echo $builder_data;
        }
        ?>
    </div>

    <div class="btn-group-vertical autolead-toolbar">
        <div class="btn btn-default" data-toggle="tooltip" title="Widget" data-widget_type="widget">
            <span class="glyphicon glyphicon-sound-dolby"></span>
        </div>
        <div class="btn btn-default" data-toggle="tooltip" title="Textarea" data-widget_type="textarea">
            <span class="glyphicon glyphicon-comment"></span>
        </div>
        <div class="btn btn-default" data-toggle="tooltip" title="Input Box" data-widget_type="textinput">
            <span class="glyphicon glyphicon-pencil"></span>
        </div>
        <div class="btn btn-default" data-toggle="tooltip" title="Choices" data-widget_type="choice">
            <span class="glyphicon glyphicon-ok"></span>
        </div>
        <div class="btn btn-default" data-toggle="tooltip" title="List" data-widget_type="list">
            <span class="glyphicon glyphicon-list-alt"></span>
        </div>
        <div class="btn btn-default" data-toggle="tooltip" title="List Items" data-widget_type="list_item">
            <span class="glyphicon glyphicon-list"></span>
        </div>
        <div class="btn btn-default autolead-bin" data-toggle="tooltip" title="Recycle Bin">
            <span class="glyphicon glyphicon-trash"></span>
        </div>
    </div>

    <div class="update-builder-wrapper">
        <button type="button" class="btn btn-primary">Save Builder Data</button>
    </div>

    <div class="hidden autolead-hidden-stock">

        <div class="autolead-widget">
            <h1 class="autolead-title" data-editable="yes">TITLE</h1>
            <div class="autolead-content">
                <div class="autolead-answer" data-type=""></div>
                <button class="autolead-action" data-editable="yes">NEXT</button>
            </div>
        </div>

        <textarea class="autolead-textarea" rows="4" data-editable="yes" value=""></textarea>

        <ul class="autolead-list">
            <li class="active"><span data-editable="yes">Answer #1</span><span class="icon"></span></li>
            <li class="inactive"><span data-editable="yes">Answer #2</span><span class="icon"></span></li>
        </ul>

        <div class="choice-selector">
            <div class="choice-active choice-left" data-editable="yes">Yes</div>
            <div class="choice-inactive choice-right" data-editable="yes">No</div>
        </div>

        <input class="autolead-input" type="text" data-editable="yes" value="" />

    </div>

</div>