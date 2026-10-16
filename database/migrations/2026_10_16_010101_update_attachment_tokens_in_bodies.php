<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Inline images in old conversations, saved replies and signatures contain
 * attachment URLs with the legacy MD5 token, which is no longer accepted
 * since GHSA-89ww-mfww-5vr6. Replace those tokens with the current ones.
 *
 * https://github.com/freescout-help-desk/freescout/issues/5665
 */
class UpdateAttachmentTokensInBodies extends Migration
{
    // ...attachment/1/2/3/image.png?id=123&amp;token=<32 hex>
    const URL_REGEX = '#(/storage/attachment/[^"\'\s<>?]+\?id=)(\d+)((?:&|&amp;)token=)([0-9a-f]{32})(?![0-9a-f])#i';

    const CHUNK = 500;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // We can't use Attachment methods here as they may not be available
        // during the process of updating the application.
        $columns = [
            ['threads', 'body'],
            ['threads', 'body_original'],
            ['mailboxes', 'signature'],
            // Saved Replies module.
            ['saved_replies', 'text'],
        ];

        foreach ($columns as $column) {
            list($table, $field) = $column;
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $field)) {
                continue;
            }
            $this->updateColumn($table, $field);
        }
    }

    public function updateColumn($table, $field)
    {
        $last_id = 0;

        do {
            $rows = \DB::table($table)
                ->select(['id', $field])
                ->where('id', '>', $last_id)
                ->where($field, 'like', '%/storage/attachment/%token=%')
                ->orderBy('id')
                ->limit(self::CHUNK)
                ->get();

            if (!count($rows)) {
                break;
            }
            $last_id = $rows->last()->id;

            // Attachments referenced in this chunk.
            $ids = [];
            foreach ($rows as $row) {
                preg_match_all(self::URL_REGEX, (string)$row->$field, $m);
                foreach ($m[2] as $id) {
                    $ids[(int)$id] = true;
                }
            }
            if (!$ids) {
                continue;
            }
            $attachments = \DB::table('attachments')
                ->select(['id', 'size', 'file_name'])
                ->whereIn('id', array_keys($ids))
                ->get()
                ->keyBy('id');

            foreach ($rows as $row) {
                $value = preg_replace_callback(self::URL_REGEX, function ($m) use ($attachments) {
                    $attachment = $attachments[(int)$m[2]] ?? null;
                    if (!$attachment) {
                        return $m[0];
                    }
                    // Same as Attachment::getToken().
                    $token = hash_hmac('sha256', $attachment->id.$attachment->size.$attachment->file_name, config('app.key'));

                    return $m[1].$m[2].$m[3].$token;
                }, (string)$row->$field);

                if ($value !== (string)$row->$field) {
                    \DB::table($table)
                        ->where('id', $row->id)
                        ->update([$field => $value]);
                }
            }
        } while (count($rows) == self::CHUNK);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Legacy tokens can not be restored.
    }
}
