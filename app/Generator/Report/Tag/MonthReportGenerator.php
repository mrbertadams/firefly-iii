<?php
/**
 * MonthReportGenerator.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Report\Tag;


use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Generator\Report\Support;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Log;

/**
 * Class MonthReportGenerator
 *
 * @package FireflyIII\Generator\Report\Tag
 */
class MonthReportGenerator extends Support implements ReportGeneratorInterface
{

    /** @var Collection */
    private $accounts;
    /** @var  Carbon */
    private $end;
    /** @var Collection */
    private $expenses;
    /** @var Collection */
    private $income;
    /** @var  Carbon */
    private $start;
    /** @var Collection */
    private $tags;

    /**
     * MonthReportGenerator constructor.
     */
    public function __construct()
    {
        $this->expenses = new Collection;
        $this->income   = new Collection;
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        $accountIds      = join(',', $this->accounts->pluck('id')->toArray());
        $tagTags         = join(',', $this->tags->pluck('tag')->toArray());
        $reportType      = 'tag';
        $expenses        = $this->getExpenses();
        $income          = $this->getIncome();
        $accountSummary  = $this->getObjectSummary($this->summarizeByAccount($expenses), $this->summarizeByAccount($income));
        $tagSummary      = $this->getObjectSummary($this->summarizeByTag($expenses), $this->summarizeByTag($income));
        $averageExpenses = $this->getAverages($expenses, SORT_ASC);
        $averageIncome   = $this->getAverages($income, SORT_DESC);
        $topExpenses     = $this->getTopExpenses();
        $topIncome       = $this->getTopIncome();


        // render!
        return view(
            'reports.tag.month', compact(
                                   'accountIds', 'tagTags', 'reportType', 'accountSummary', 'tagSummary', 'averageExpenses', 'averageIncome', 'topIncome',
                                   'topExpenses'
                               )
        )->with('start', $this->start)->with('end', $this->end)->with('tags', $this->tags)->with('accounts', $this->accounts)->render();
    }

    /**
     * @param Collection $accounts
     *
     * @return ReportGeneratorInterface
     */
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * @param Collection $budgets
     *
     * @return ReportGeneratorInterface
     */
    public function setBudgets(Collection $budgets): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * @param Collection $categories
     *
     * @return ReportGeneratorInterface
     */
    public function setCategories(Collection $categories): ReportGeneratorInterface
    {
        return $this;
    }

    /**
     * @param Carbon $date
     *
     * @return ReportGeneratorInterface
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * @param Carbon $date
     *
     * @return ReportGeneratorInterface
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }

    /**
     * @param Collection $tags
     *
     * @return ReportGeneratorInterface
     */
    public function setTags(Collection $tags): ReportGeneratorInterface
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return Collection
     */
    protected function getExpenses(): Collection
    {
        if ($this->expenses->count() > 0) {
            Log::debug('Return previous set of expenses.');

            return $this->expenses;
        }

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setTags($this->tags)->withOpposingAccount()->disableFilter();

        $accountIds     = $this->accounts->pluck('id')->toArray();
        $transactions   = $collector->getJournals();
        $transactions   = self::filterExpenses($transactions, $accountIds);
        $this->expenses = $transactions;

        return $transactions;
    }

    /**
     * @return Collection
     */
    protected function getIncome(): Collection
    {
        if ($this->income->count() > 0) {
            return $this->income;
        }

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)
                  ->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->setTags($this->tags)->withOpposingAccount();
        $accountIds   = $this->accounts->pluck('id')->toArray();
        $transactions = $collector->getJournals();
        $transactions = self::filterIncome($transactions, $accountIds);
        $this->income = $transactions;

        return $transactions;
    }

    /**
     * @param Collection $collection
     *
     * @return array
     */
    protected function summarizeByTag(Collection $collection): array
    {
        $result = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $journal     = $transaction->transactionJournal;
            $journalTags = $journal->tags;
            /** @var Tag $journalTag */
            foreach ($journalTags as $journalTag) {
                $journalTagId          = $journalTag->id;
                $result[$journalTagId] = $result[$journalTagId] ?? '0';
                $result[$journalTagId] = bcadd($transaction->transaction_amount, $result[$journalTagId]);
            }
        }

        return $result;
    }
}
