<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php
        if (is_object($this->Message))
            echo t('Edit Message');
        else
            echo t('Add Message');
        ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Appearance', 'CssClass'); ?>
            </div>
            <div class="input-wrap">
                <?php
                echo $this->Form->Radio('CssClass', t('Casual'), ['value' => 'CasualMessage']);
                echo $this->Form->Radio('CssClass', t('Information'), ['value' => 'InfoMessage']);
                echo $this->Form->Radio('CssClass', t('Alert'), ['value' => 'AlertMessage']);
                echo $this->Form->Radio('CssClass', t('Warning'), ['value' => 'WarningMessage']);
                ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $this->Form->label('Message', 'Content'); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->textBox('Content', ['MultiLine' => TRUE]); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Page', 'Location'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->DropDown('Location', $this->data('Locations')); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Position', 'AssetTarget'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->DropDown('AssetTarget', $this->AssetData); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Category', 'CategoryID'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->DropDown('CategoryID', $this->data('Categories'), ['IncludeNull' => t('All Categories')]); ?>
            </div>
            <div class="input-wrap no-label padded-top">
                <?php echo $this->Form->CheckBox('IncludeSubcategories', 'Include Subcategories'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('AllowDismiss', 'Allow users to dismiss this message', ['value' => '1']); ?>
            </div>
        </li>
        <li class="form-group">
            <?php echo $this->Form->toggle('Enabled', 'Enable this message', ['value' => '1']); ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
