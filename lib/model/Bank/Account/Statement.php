<?php
/**
 * Bank_Account_Statement class
 *
 * @author David Vandemaele <david@tigron.be>
 */

use \Skeleton\Database\Database;

class Bank_Account_Statement {
	use \Skeleton\Object\Model;
	use \Skeleton\Object\Get;
	use \Skeleton\Object\Save;
	use \Skeleton\Object\Delete;
	use \Skeleton\Pager\Page;

	/**
	 * Get bank_account_statement_transaction
	 *
	 * @access public
	 */
	public function get_bank_account_statement_transactions() {
		return Bank_Account_Statement_Transaction::get_by_bank_account_statement($this);
	}

	/**
	 * Populate
	 *
	 * @access public
	 */
	public function populate() {
		$transactions = Bank_Account_Statement_Transaction::get_by_bank_account_statement($this);
		$last = null;

		$balance = 0;

		foreach ($transactions as $transaction) {
			$balance += $transaction->amount;

			if ($last === null) {
				$last = $transaction;
				continue;
			}

			if (strtotime($last->date) < strtotime($transaction->date)) {
				$last = $transaction;
			}
		}
		$this->date = $last->date;

		try {
			$previous = $this->get_previous();
			$this->original_situation_balance = $previous->new_situation_balance;
		} catch (Exception $e) {
			$this->original_situation_balance = 0;
		}

		$this->new_situation_balance = $this->original_situation_balance + $balance;
		$this->save();
	}

	/**
	 * Get previous
	 *
	 * @access public
	 * @return Bank_Account_Statement $statement
	 */
	public function get_previous() {
		$db = Database::get();
		$id = $db->get_one('SELECT id FROM bank_account_statement WHERE bank_account_id=? AND sequence < ? ORDER BY sequence DESC LIMIT 1', [ $this->bank_account_id, $this->sequence ]);
		if ($id === null) {
			throw new Exception('No previous statement found');
		}
		return self::get_by_id($id);
	}

	/**
	 * Get by bank_account identifier
	 *
	 * @access public
	 * @param Bank_Account $bank_account
	 * @param string $sequence
	 * @return Bank_Account_Statement $bank_account_statement
	 */
	public static function get_by_bank_account_sequence(Bank_Account $bank_account, $sequence) {
		$db = Database::get();
		$id = $db->get_one('SELECT id FROM bank_account_statement WHERE bank_account_id=? AND sequence=?', [ $bank_account->id, $sequence ]);
		if ($id === null) {
			throw new Exception('Bank account statement for bank account ' . $bank_account->identifier . ' and sequence ' . $sequence . ' not found');
		}
		return self::get_by_id($id);
	}

	/**
	 * Get by bank_account identifier
	 *
	 * @access public
	 * @param Bank_Account $bank_account
	 * @return array $bank_account_statement
	 */
	public static function get_by_bank_account(Bank_Account $bank_account) {
		$db = Database::get();
		$ids = $db->get_column('SELECT id FROM bank_account_statement WHERE bank_account_id=? ORDER BY original_situation_date ASC', [ $bank_account->id ]);

		$statements = [];
		foreach ($ids as $id) {
			$statements[] = self::get_by_id($id);
		}
		return $statements;
	}

	/**
	 * Get by bank_account identifier
	 *
	 * @access public
	 * @param Bank_Account $bank_account
	 * @param string $year
	 * @return array $bank_account_statement
	 */
	public static function get_by_bank_account_year(Bank_Account $bank_account, $year) {
		$db = Database::get();
		$ids = $db->get_column('SELECT id FROM bank_account_statement WHERE bank_account_id=? AND YEAR(original_situation_date) = ? ORDER BY original_situation_date DESC', [ $bank_account->id, $year ]);

		$statements = [];
		foreach ($ids as $id) {
			$statements[] = self::get_by_id($id);
		}
		return $statements;
	}

	/**
	 * Get by bank_account identifier
	 *
	 * @access public
	 * @param Bank_Account $bank_account
	 * @return Bank_Account_Statement $bank_account_statement
	 */
	public static function get_last_by_bank_account(Bank_Account $bank_account) {
		$db = Database::get();
		$id = $db->get_one('SELECT id FROM bank_account_statement WHERE bank_account_id=? ORDER BY original_situation_date DESC LIMIT 1', [ $bank_account->id ]);

		if ($id === null) {
			throw new Exception('No statements available');
		}

		return self::get_by_id($id);
	}
}
