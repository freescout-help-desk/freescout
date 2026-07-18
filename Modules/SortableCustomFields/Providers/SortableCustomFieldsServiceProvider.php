<?php

namespace Modules\SortableCustomFields\Providers;

use Modules\CustomFields\Entities\CustomField;
use Illuminate\Support\ServiceProvider;

define('CF_SORTABLE_MODULE', 'sortablecustomfields');

class SortableCustomFieldsServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->hooks();
    }
    public static function createSlug($str, $delimiter = '_')
    {
        $slug = \Str::slug($str, $delimiter, 'en');
        return $slug;
    }
    public function hooks()
    {
          // Add module's JS file to the application layout.
          \Eventy::addFilter('javascripts', function ($javascripts) {
     
            $javascripts[] = \Module::getPublicPath(CF_SORTABLE_MODULE) . '/js/module.js';

            return $javascripts;
        });

       
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(CF_SORTABLE_MODULE).'/css/style.css';
            return $styles;
        });

        // Sort by custom fields
        //
        // threls fork patch: upstream concatenated $_REQUEST['sorting']['sort_by']
        // straight into a DB::Raw() string used as a LIKE pattern — an
        // authenticated agent could break out of the SQL string literal via the
        // conversation list's sort param (SQL injection). It also matched via
        // LIKE against the slugified name, where '_' is a SQL wildcard for "any
        // one character", so a slug could false-positive-match the wrong field.
        // Fixed by resolving the request's slug against this mailbox's real
        // CustomField names first (untrusted input never reaches SQL) and
        // building the join/alias only from that trusted, already-slugged value.
        \Eventy::addFilter('folder.conversations_query', function ($query_conversations) {

            if (isset($_REQUEST['sorting']['sort_by']) && strpos($_REQUEST['sorting']['sort_by'], 'custom_') === 0) {
                $requestedSlug = str_replace('custom_', '', $_REQUEST['sorting']['sort_by']);
                $order = strtolower($_REQUEST['sorting']['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
                $mailbox_id = request()->mailbox_id ?? request()->id ?? 0;

                $sortField = null;
                if ($mailbox_id) {
                    $sortField = CustomField::where('mailbox_id', $mailbox_id)
                        ->distinct('name')
                        ->get()
                        ->first(function ($customField) use ($requestedSlug) {
                            return self::createSlug($customField->name, '_') === $requestedSlug;
                        });
                }

                if ($sortField) {
                    $quotedName = \DB::connection()->getPdo()->quote($sortField->name);
                    $alias = 'sort_' . self::createSlug($sortField->name, '_');

                    $query_conversations = $query_conversations->leftJoin(\DB::raw('(select conversation_custom_field.custom_field_id, conversation_custom_field.conversation_id, conversation_custom_field.value, custom_fields.name from conversation_custom_field left join custom_fields on conversation_custom_field.custom_field_id = custom_fields.id where custom_fields.name = ' . $quotedName . ') a'), 'a.conversation_id', '=', 'conversations.id');
                    $query_conversations = $query_conversations->selectRaw('conversations.*, a.value as ' . $alias);
                    $query_conversations = $query_conversations->orderBy($alias, $order);
                }
            }
            return $query_conversations;

        });


        \Eventy::addAction('conversations_table.col_before_conv_number', function ($conversation) {

            $mailbox_id = request()->mailbox_id ?? request()->id ?? 0;
       
        if ($mailbox_id) {
            $custom_fields = CustomField::where('mailbox_id', $mailbox_id)
                // groupBy('name') does not work in PostgreSQL.
                ->distinct('name')
                ->get();
        }


        if (isset($custom_fields) && count($custom_fields)) {
            foreach ($custom_fields as $custom_field) {
                if (!$custom_field->show_in_list){
                    continue;
                }
                $slug= $this->createSlug($custom_field->name, "_");
                ob_start()
                    ?>
                    <col class="conv-<?=  $slug ?>">
               
                <?php
                $output = ob_get_clean();
                echo $output;
            }
        }
        }, 20, 3);
        \Eventy::addAction('conversations_table.th_before_conv_number', function () {
            $sorting=['sort_by'=>'date','order'=>'asc'];

            if ( isset($_REQUEST['sorting'])){  
                $sorting['sort_by'] = request()->sorting['sort_by'];
                $sorting['order'] = request()->sorting['order'];
              

            }
          
            $mailbox_id = request()->mailbox_id ?? request()->id ?? 0;
       
            if ($mailbox_id) {
                $custom_fields = CustomField::where('mailbox_id', $mailbox_id)
                    // groupBy('name') does not work in PostgreSQL.
                    ->distinct('name')
                    ->get();
            }


            if (isset($custom_fields) && count($custom_fields)) {
                foreach ($custom_fields as $custom_field) {
                    if (!$custom_field->show_in_list){
                        continue;
                    }
                    $slug= $this->createSlug($custom_field->name, "_");
                    ob_start()
                        ?>
                    <th class="custom-field-th">
                        <span class="conv-col-sort custom-field-tr" data-sort-by="custom_<?=  $slug ?>" data-order="<?=  ($sorting['sort_by'] ==  'custom_'.$slug) ? $sorting['order']:'desc' ?>">
                            <?=  e(__($custom_field->name)) ?>
                            <?= ($sorting['sort_by'] == 'custom_'.$slug && $sorting['order'] =='asc')? '↓' : '' ?>
                            <?= ($sorting['sort_by'] == 'custom_'.$slug && $sorting['order'] =='desc')? '↑' : ''?>
                        </span>
                    </th>
                    <?php
                    $output = ob_get_clean();
                    echo $output;
                }
            }


        }, 20, 3);

     \Eventy::addAction('conversations_table.td_before_conv_number', function ($conversation) {
         if (isset($conversation->custom_fields)){
            foreach ($conversation->custom_fields as $custom_field){
                ob_start()
                ?>
                
                     <td class="custom-field-td <?= $this->createCSSClassForCustomField($custom_field) ?>">
                     <a href="<?= $conversation->url() ?>" title="<?= __('View conversation') ?>"><?= e($custom_field->getAsText()) ?></a>
                     </td>
                <?php
                $output = ob_get_clean();
                echo $output;
            }
        }
      

        }, 20, 3);
     
        \Eventy::addAction('conversations_table.row_class', function($conversation) {
       
            if (isset($conversation->custom_fields)){
                
                foreach ($conversation->custom_fields as $custom_field){
                    echo " ";
                    echo $this->createCSSClassForCustomField($custom_field);
                    echo " ";
                }
            }
          
        });
    
    }

    private function createCSSClassForCustomField($custom_field) {
        $propName = $this->createSlug($custom_field->name, "-");
        $propValue = $this->createSlug($custom_field->getAsText(), "-");
        return 'cf_' . $propName . '_' . $propValue;
    }

  

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('sortablecustomfields.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'sortablecustomfields'
        );
    }

   
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
