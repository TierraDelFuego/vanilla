<?php
/**
 * GooglePrettify Plugin.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package GooglePrettify
 */

// Changelog
// v1.1 Add Tabby, docs/cleanup  -Lincoln, Aug 2012

/**
 * Class GooglePrettifyPlugin
 */
class GooglePrettifyPlugin extends Gdn_Plugin {

    /**
     * Add Prettify to page text.
     */
    public function addPretty($Sender) {
        $Sender->Head->addTag('script', ['type' => 'text/javascript', '_sort' => 100], $this->GetJs());
        $Sender->addJsFile('prettify.js', 'plugins/GooglePrettify', ['_sort' => 101]);
        if ($Language = c('Plugins.GooglePrettify.Language')) {
            $Sender->addJsFile("lang-$Language.js", 'plugins/GooglePrettify', ['_sort' => 102]);
        }
    }

    /**
     * Add Tabby to a page's text areas.
     */
    public function addTabby($Sender) {
        if (c('Plugins.GooglePrettify.UseTabby', false)) {
            $Sender->addJsFile('jquery.textarea.js', 'plugins/GooglePrettify');
            $Sender->Head->addTag('script', ['type' => 'text/javascript', '_sort' => 100], '
        function init() {
            $("textarea").not(".Tabby").addClass("Tabby").tabby();
        }
        $(document).on("contentLoad", init);');
        }
    }

    /**
     * Prettify script initializer.
     *
     * @return string
     */
    public function getJs() {
        $Class = '';
        if (c('Plugins.GooglePrettify.LineNumbers')) {
            $Class .= ' linenums';
        }
        if ($Language = c('Plugins.GooglePrettify.Language')) {
            $Class .= " lang-$Language";
        }

        $Result = "
            function init() {
                $('.Message').each(function () {
                    if ($(this).data('GooglePrettify')) {
                        return;
                    }
                    $(this).data('GooglePrettify', '1');

                    pre = $('pre', this).addClass('prettyprint$Class');

                    // Let prettyprint determine styling, rather than the editor.
                    $('code', this).removeClass('CodeInline');
                    pre.removeClass('CodeBlock');

                    prettyPrint();

                    pre.removeClass('prettyprint');
                });
            }

            $(document).on('contentLoad', init);";
        return $Result;
    }

    public function assetModel_styleCss_handler($Sender) {
        if (!c('Plugins.GooglePrettify.NoCssFile')) {
            $Sender->addCssFile('prettify.css', 'plugins/GooglePrettify');
        }
    }

    public function assetModel_generateETag_handler($Sender, $Args) {
        if (!c('Plugins.GooglePrettify.NoCssFile')) {
            $Args['ETagData']['Plugins.GooglePrettify.NoCssFile'] = true;
        }
    }

    /**
     * Add Prettify formatting to discussions.
     *
     * @param DiscussionController $Sender
     */
    public function discussionController_render_before($Sender) {
        $this->addPretty($Sender);
        $this->addTabby($Sender);
    }

    /**
     * Add Tabby to post textarea.
     *
     * @param PostController $Sender
     */
    public function postController_render_before($Sender) {
        $this->addPretty($Sender);
        $this->addTabby($Sender);
    }

    /**
     * Add Tabby to conversations textarea.
     *
     * @param MessagesController $Sender
     */
    public function messagesController_render_before($Sender) {
        $this->addPretty($Sender);
        $this->addTabby($Sender);
    }

    /**
     * Add Prettify formatting to profile posts.
     *
     * @param DiscussionController $Sender
     */
    public function profileController_render_before($Sender) {
        $this->addPretty($Sender);
        $this->addTabby($Sender);
    }

    /**
     * Settings page.
     *
     * @param unknown_type $Sender
     * @param unknown_type $Args
     */
    public function settingsController_googlePrettify_create($Sender, $Args) {
        $Cf = new ConfigurationModule($Sender);
        $CssUrl = asset('/plugins/GooglePrettify/design/prettify.css', true);

        $Languages = [
            'apollo' => 'apollo',
            'clj' => 'clj',
            'css' => 'css',
            'go' => 'go',
            'hs' => 'hs',
            'lisp' => 'lisp',
            'lua' => 'lua',
            'ml' => 'ml',
            'n' => 'n',
            'proto' => 'proto',
            'scala' => 'scala',
            'sql' => 'sql',
            'text' => 'tex',
            'vb' => 'visual basic',
            'vhdl' => 'vhdl',
            'wiki' => 'wiki',
            'xq' => 'xq',
            'yaml' => 'yaml'
        ];

        $Cf->initialize([
            'Plugins.GooglePrettify.LineNumbers' => ['Control' => 'CheckBox', 'Description' => 'Add line numbers to source code.', 'Default' => false],
            'Plugins.GooglePrettify.NoCssFile' => ['Control' => 'CheckBox', 'LabelCode' => 'Exclude Default CSS File', 'Description' => "If you want to define syntax highlighting in your custom theme you can disable the <a href='$CssUrl'>default css</a> with this setting.", 'Default' => false],
            'Plugins.GooglePrettify.UseTabby' => ['Control' => 'CheckBox', 'LabelCode' => 'Allow Tab Characters', 'Description' => "If users enter a lot of source code then enable this setting to make the tab key enter a tab instead of skipping to the next control.", 'Default' => false],
            'Plugins.GooglePrettify.Language' => ['Control' => 'DropDown', 'Items' => $Languages, 'Options' => ['IncludeNull' => true],
                'Description' => 'We try our best to guess which language you are typing in, but if you have a more obscure language you can force all highlighting to be in that language. (Not recommended)']
        ]);


        $Sender->setData('Title', t('Syntax Prettifier Settings'));
        $Cf->renderAll();
    }
}
