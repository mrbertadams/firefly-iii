<?php
/**
 * TransactionControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::index
     * @covers \FireflyIII\Http\Controllers\TransactionController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('first')->times(2)->andReturn(new TransactionJournal);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('disableInternalFilter')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['transfer']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::indexAll
     */
    public function testIndexAll()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('disableInternalFilter')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $this->be($this->user());
        $response = $this->get(route('transactions.index.all', ['transfer']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::indexByDate
     */
    public function testIndexByDate()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('disableInternalFilter')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $this->be($this->user());
        $response = $this->get(route('transactions.index.date', ['transfer', '2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::reorder
     */
    public function testReorder()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $data = [
            'items' => [],
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.reorder'), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::show
     */
    public function testShow()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $tasker     = $this->mock(JournalTaskerInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $tasker->shouldReceive('getPiggyBankEvents')->andReturn(new Collection);
        $tasker->shouldReceive('getTransactionsOverview')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('transactions.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Controller::redirectToAccount
     * @covers \FireflyIII\Http\Controllers\TransactionController::show
     */
    public function testShowOpeningBalance()
    {
        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $journal  = $this->user()->transactionJournals()->where('transaction_type_id', 4)->first();
        $response = $this->get(route('transactions.show', [$journal->id]));
        $response->assertStatus(302);
    }

}
