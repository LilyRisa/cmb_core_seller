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

    public function test_is_audio_like_matches_kind_audio_and_file_with_audio_mime(): void
    {
        // FB voice thường về kind=file mime audio/* — phải nhận là audio để STT.
        $this->assertTrue((new MessageAttachment(['kind' => 'audio', 'mime' => 'audio/mpeg']))->isAudioLike());
        $this->assertTrue((new MessageAttachment(['kind' => 'file', 'mime' => 'audio/mpeg']))->isAudioLike());
        $this->assertFalse((new MessageAttachment(['kind' => 'file', 'mime' => 'application/pdf']))->isAudioLike());
        $this->assertFalse((new MessageAttachment(['kind' => 'image', 'mime' => 'image/jpeg']))->isAudioLike());
    }
}
