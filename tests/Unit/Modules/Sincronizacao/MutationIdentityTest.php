<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Sincronizacao;

use App\Modules\Sincronizacao\Domain\MutationIdentity;
use PHPUnit\Framework\TestCase;

final class MutationIdentityTest extends TestCase
{
    public function testRetryPreservesIdentityButActorOrBodyChangesDoNot(): void
    {
        $identity = MutationIdentity::create('offline-unique-123', 2, '{"gols":1}');
        self::assertEquals($identity, MutationIdentity::create('offline-unique-123', 2, '{"gols":1}'));
        self::assertNotSame($identity->fingerprint, MutationIdentity::create('offline-unique-123', 1, '{"gols":1}')->fingerprint);
        self::assertNotSame($identity->fingerprint, MutationIdentity::create('offline-unique-123', 2, '{"gols":2}')->fingerprint);
        self::assertNull(MutationIdentity::create('', 2, '{}')->key);
    }

    public function testMalformedKeysAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MutationIdentity::create('../invalid key', 2, '{}');
    }
}
