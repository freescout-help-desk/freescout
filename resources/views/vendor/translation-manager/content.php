<style>
    a.status-1{
        font-weight: bold;
    }
</style>

<?php
    $selected_locale = request()->input('locale');
    if ($selected_locale == 'en') {
        $selected_locale = '';
    }
    if (!$selected_locale) {
        foreach ($locales as $locale) {
            if ($locale != 'en') {
                $selected_locale = $locale;
                break;
            }
        }
    }
?>

<div class="container">

<div class="container-fluid">

	<div class="heading margin-bottom">Translate</div>

    <div class="panel panel-default panel-shaded">
        <div class="panel-heading">Find Translations</div>
        <div class="panel-body">

            <div class="alert alert-success success-import" style="display:none;">
                <p>Done importing, processed <strong class="counter">N</strong> items! Reload this page to refresh the groups!</p>
            </div>
            <div class="alert alert-success success-find" style="display:none;">
                <p>Done searching for translations, found <strong class="counter">N</strong> items!</p>
            </div>
            <div class="alert alert-success success-publish" style="display:none;">
                <p>Done publishing translations for group '<?php echo $group ?>'!</p>
            </div>
            <?php if(Session::has('successPublish')) : ?>
                <div class="alert alert-info">
                    <?php echo Session::get('successPublish'); ?>
                </div>
            <?php endif; ?>
            <p>
                <?php if(!isset($group)) : ?>
                    <form class="form-find hidden" method="POST" action="<?php echo action('\Barryvdh\TranslationManager\Controller@postFind') ?>" data-remote="true" role="form" data-confirm="Search may take some time, please don't reload the page until the search process finishes.<?php /*Are you sure you want to scan you app folder? All found translation keys will be added to the database.*/ ?>">
                        <div class="form-group">
                            <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                            <button type="submit" class="btn btn-primary" data-disable-with="Searching…" >Find translations in files</button>
                            <button type="submit" class="btn btn-primary" name="submit" value="modules" data-disable-with="Searching…" >Find translations in modules</button>
                        </div>
                    </form>
                    <form class="form-import" method="POST" action="<?php echo action('\Barryvdh\TranslationManager\Controller@postImport') ?>" data-remote="true" role="form">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        <div class="form-group">
                            <p>1. Import existing translations (non-published pending translations will be overwritten).</p>
                            <div class="row">
                                <div class="col-sm-12">
                                <?php /*<div class="col-sm-3">*/ ?>
                                    <select name="replace" class="form-control hidden">
                                        <option value="0">Append new translations</option>
                                        <option value="1" selected>Replace existing translations</option>
                                    </select>
                                <?php /*</div>
                                <div class="col-sm-2">*/ ?>
                                <button type="submit" class="btn btn-primary"  data-disable-with="Importing… It may take several minutes">Import Translations</button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </p>
            <form role="form" method="POST" action="<?php echo action('\Barryvdh\TranslationManager\Controller@postAddGroup') ?>">
                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                <div class="form-group">
                    <p><?php if (!isset($group)) : ?>2.<?php endif ?>Choose a group to display translations. <?php /* (if no groups are visisble, make sure you have imported translations).*/ ?></p>
                    <select name="group" id="group" class="form-control group-select" autocomplete="off">
                        <?php foreach($groups as $key => $value): ?>
                            <option value="<?php echo $key ?>"<?php echo $key == $group ? ' selected':'' ?>><?php if (strstr($value, 'a group')): ?>-- <?php endif ?><?php echo ucfirst(trim($value, '_')) ?><?php if (strstr($value, 'a group')): ?> --<?php endif ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php /*<div class="form-group">
                    <label>Enter a new group name and start edit translations in that group</label>
                    <input type="text" class="form-control" name="new-group" />
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-default" name="add-group" value="Add and edit keys" />
                </div>*/ ?>
            </form>
            <?php if (isset($group)) : ?>
                <form role="form" method="GET" action="">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <div class="form-group">
                        <p>Language</p>
                        <select name="locale" id="locale" class="form-control group-locale-select" autocomplete="off">
                            <?php foreach($locales as $locale): ?>
                                <?php if ($locale != 'en'): ?>
                                    <option value="<?php echo $locale ?>"<?php echo $locale == $selected_locale ? ' selected':'' ?>><?php echo \Helper::getLocaleData($locale, 'name') ?> (<?php echo \Helper::getLocaleData($locale, 'name_en') ?>)</option>
                                <?php endif ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            <?php endif ?>
            <?php if(isset($group)) : ?>
                <form class="form-inline form-publish" method="POST" action="<?php echo action('\Barryvdh\TranslationManager\Controller@postPublish', $group) ?>" data-remote="true" role="form" data-confirm="Are you sure you want to publish the translations group '<?php echo $group ?>? This will overwrite existing language files.">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <?php /*<button type="submit" class="btn btn-primary" data-disable-with="Publishing…" >Publish translations</button>*/ ?>
                    <a href="<?= action('\Barryvdh\TranslationManager\Controller@getIndex') ?>" class="btn btn-primary">« Back</a>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel panel-default panel-shaded">
        <?php if (!$group): ?>
            <div class="panel-heading">Languages</div>
        <?php endif ?>
        <div class="panel-body">
            <?php if($group): ?>
                <?php /*<form action="<?php echo action('\Barryvdh\TranslationManager\Controller@postAdd', array($group)) ?>" method="POST"  role="form">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    <div class="form-group">
                        <label>Add new keys to this group</label>
                        <textarea class="form-control" rows="3" name="keys" placeholder="Add 1 key per line, without the group prefix"></textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="Add keys" class="btn btn-primary">
                    </div>
                </form>
                <hr>*/ ?>
                <h4>Total: <?= $numTranslations ?>, changed: <?= $numChanged ?></h4>
                <table class="table">
                    <thead>
                    <tr>
                        <th width="15%">Key</th>
                        <?php foreach ($locales as $locale): ?>
                            <?php if ($locale == $selected_locale || ($locale == 'en' && $group[0] != '_')): ?>
                                <th><?= $locale ?></th>
                            <?php endif ?>
                        <?php endforeach; ?>
                        <?php /*if ($deleteEnabled): ?>
                            <th>&nbsp;</th>
                        <?php endif;*/ ?>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach ($translations as $key => $translation): ?>
                        <tr id="<?php echo htmlentities($key, ENT_QUOTES, 'UTF-8', false) ?>">
                            <td><?php echo htmlentities($key, ENT_QUOTES, 'UTF-8', false) ?></td>
                            <?php foreach ($locales as $locale): ?>
                                <?php if (!($locale == $selected_locale || ($locale == 'en' && $group[0] != '_'))): ?>
                                    <?php continue; ?>
                                <?php endif ?>
                                <?php $t = isset($translation[$locale]) ? $translation[$locale] : null ?>

                                <td>
                                    <a href="#edit"
                                       class="editable status-<?php echo $t ? $t->status : 0 ?> locale-<?php echo $locale ?>"
                                       data-locale="<?php echo $locale ?>" data-name="<?php echo $locale . "|" . htmlentities($key, ENT_QUOTES, 'UTF-8', false) ?>"
                                       id="username" data-type="textarea" data-pk="<?php echo $t ? $t->id : 0 ?>"
                                       data-url="<?php echo $editUrl ?>"
                                       data-title="Enter translation"><?php echo $t ? htmlentities($t->value, ENT_QUOTES, 'UTF-8', false) : '' ?></a>
                                </td>
                            <?php endforeach; ?>
                            <?php /*if ($deleteEnabled): ?>
                                <td>
                                    <a href="<?php echo action('\Barryvdh\TranslationManager\Controller@postDelete', [$group, $key]) ?>"
                                       class="delete-key"
                                       data-confirm="Are you sure you want to delete the translations for '<?php echo htmlentities($key, ENT_QUOTES, 'UTF-8', false) ?>?"><span
                                                class="glyphicon glyphicon-trash"></span></a>
                                </td>
                            <?php endif;*/ ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <fieldset>
                    <?php /*<p>
                        Currently supported locales:
                    </p>*/ ?>
                    <form  class="form-remove-locale" method="POST" role="form" action="<?php echo action('\Barryvdh\TranslationManager\Controller@postRemoveLocale') ?>" data-confirm="Are you sure to remove this locale and all of data?">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        <ul class="list-locales">
                        <?php foreach($locales as $locale): ?>
                            <li>
                                <div class="form-group">
                                    <strong><?php echo $locale ?></strong>
                                    <?php if (!in_array($locale, config('app.locales'))): ?>
                                        <button type="submit" name="remove-locale[<?php echo $locale ?>]" class="btn btn-link btn-xs" data-disable-with="...">
                                            &times;
                                        </button>
                                    <?php endif ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </form>
                    <form class="form-add-locale" method="POST" role="form" action="<?php echo action('\Barryvdh\TranslationManager\Controller@postAddLocale') ?>">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        <div class="form-group">
                            <?php /*<p>
                                Add new locale:
                            </p>*/ ?>
                            <div class="row">
                                <div class="col-sm-4 col-md-3">
                                    <select name="new-locale" class="form-control">
                                        <?php foreach (\Helper::$locales as $locale_code => $locale_info): ?>
                                            <option value="<?php echo $locale_code; ?>"><?php echo $locale_info['name_en']; ?> (<?php echo $locale_info['name']; ?>)</option>
                                        <?php endforeach ?>
                                    </select>
                                    <?php /*<input type="text" name="new-locale" class="form-control" />*/ ?>
                                </div>
                                <div class="col-sm-3">
                                    <button type="submit" class="btn btn-default"  data-disable-with="Adding…">Add New Langauge</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </fieldset>
            </div>
        </div>

        <div class="panel panel-default panel-shaded">
            <div class="panel-heading">Publish Translations</div>
            <div class="panel-body">
                <fieldset>
                    <p class="block-help">
                        Translations are not visible in the application until they are published.
                    </p>
                    <p class="block-help margin-bottom">
                        If you want your translations to be added to the application release, you can send translations to the <?php echo \Config::get('app.name') ?> team.
                    </p>
                    <div class="alert alert-success success-publish-all" style="display:none;">
                        <p>Translations published!</p>
                    </div>
                    <div class="alert alert-success success-send-translations" style="display:none;">
                        <p>Translations sent!</p>
                    </div>
                    <div class="alert alert-danger error-send-translations" style="display:none;">
                        <p>Error occured sending translations. <a href="<?php echo route('system') ?>#php" target="_blank">Make sure</a> that you have PHP Zip extension enabled and check your <a href="<?php echo route('settings', ['section' => 'emails']) ?>" target="_blank">mail settings</a>.</p>
                    </div>
                    <div class="alert alert-success success-remove-unpublished" style="display:none;">
                        <p>Non-published translations removed!</p>
                    </div>
                    <form class="form-inline form-publish-all pull-left" method="POST" action="<?php echo action('\Barryvdh\TranslationManager\Controller@postPublish', '*') ?>" data-remote="true" role="form" data-confirm="Are you sure you want to publish all translation groups? This will overwrite existing language files.">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        <button type="submit" class="btn btn-primary" data-disable-with="Publishing…" >Publish Translations</button>
                    </form>
                    <form class="form-inline form-send-translations pull-left" method="POST" action="<?php echo action('TranslateController@postSend') ?>" data-remote="true" role="form" data-confirm="This will publish translations and send them to <?php echo \Config::get('app.name') ?> team by email.">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        &nbsp;&nbsp;
                        <button type="submit" class="btn btn-default" data-disable-with="Sending…" >Send Translations to <?php echo \Config::get('app.name') ?> Team</button>
                    </form>
                    <form class="form-inline form-download pull-left" method="POST" target="_blank" action="<?php echo action('TranslateController@postDownload') ?>" onsubmit="javascript:confirm('This will publish translations and download them as ZIP archive.');">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        
                        <button type="submit" class="btn btn-link" data-disable-with="Preparing…" title="Download translations as ZIP archive">Download as ZIP</button>
                    </form>
                    <form class="form-inline form-remove-unpublished pull-left" method="POST" action="<?php echo action('TranslateController@postRemoveUnpublished') ?>" data-remote="true" role="form" data-confirm="Are you sure you want to remove all translations which has not been published yet?">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        
                        <button type="submit" class="btn btn-link" style="padding-left:0" data-disable-with="Removing…" title="Remove non-published translations"><span class="text-danger">Remove non-npublished</span></button>
                    </form>
                </fieldset>

            <?php endif; ?>
        </div>
    </div>
</div>
</div>