<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MessageAttachmentTranscriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_transcript_column_exists_and_fillable(): void
    {
        $this->assertTrue(Schema::hasColumn('message_attachments', 'transcript'));
        $att = new MessageAttachment;
        $att->fill(['transcript' => 'xin chào']);
        $this->assertSame('xin chào', $att->transcript);
    }
}
