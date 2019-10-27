<?php /**
* Export svn to git
* @author Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
* @link https://github.com/kktsvetkov/Haremasu
* @license GNU LESSER GENERAL PUBLIC LICENSE v.3
*/

new haremasu;

/**
* Export svn history from a folder to a cloned git repo folder
*/
class haremasu
{
	/**
	* @var string source folder (where the svn project is)
	*/
	protected $source = '';

	/**
	* @var string svn URL
	*/
	protected $svn_url = '';

	/**
	* @var string target folder (where the git project is)
	*/
	protected $target = '';

	/**
	* @var array collected svn revisions
	*/
	protected $history = [];

	public function __construct()
	{
		$this->head();

		$argv = $_SERVER['argv'];
		if (empty($argv[1]))
		{
			$this->error('Missing svn source');
		}

		$svn_url = trim(shell_exec('svn info --show-item url ' . escapeshellarg($argv[1]) ));
		if (!$svn_url)
		{
			$this->error("svn source \"{$argv[1]}\" does not contain svn information.");
		}

		$this->source = trim($argv[1]);
		$this->svn_url = $svn_url;

		if (empty($argv[2]))
		{
			$this->error("missing git target folder");
		}
		if (!is_dir($argv[2]))
		{
			$this->error("git target folder \"{$argv[2]}\" is not a folder.");
		}
		if (!is_dir($argv[2] . '/.git'))
		{
			$this->error("git target folder \"{$argv[2]}\" is not a cloned git repo (no .git folder).");
		}

		$this->target = realpath($argv[2]);

		$this->move();
	}

	protected function head()
	{
		echo "HAREMASU: export svn to git\n";
	}

	protected function error($err)
	{
		die("[!] ERROR: {$err}\n\n");
	}

	protected function move()
	{
		if (file_exists($this->source))
		{
			echo " < Source Folder: {$this->source}\n";
		}
		echo " < Source SVN URL: < {$this->svn_url}\n\n";

		chdir($this->target);
		echo " > Target Folder: {$this->target}\n";

		// git repos can only be local, with no remote urls
		//
		$url = shell_exec('git config --get remote.origin.url');
		if ($url)
		{
			echo " > Target Git URL: > {$url}\n";
		}

		// collect the svn history
		//
		$this->svnHistory();

		$i = 0;
		$j = '% ' . strlen('' . count($this->history)) . 'd/' . count($this->history);

		$this->history = array_reverse($this->history, true);
		foreach ($this->history as $revision => $data)
		{
			$p = sprintf($j, ++$i);
			echo " < [{$p}] r{$revision} | {$data[0]} | ", gmdate('r', $data[1]), "\n";

			shell_exec('svn export -q --force -r' . $revision
				. ' ' . escapeshellarg($this->source)
				. ' ' . $this->target
				);

			shell_exec('git add *');

			$message = '# SVN: ' . $this->svn_url . '@' . $revision;
			if (!empty($data[2]))
			{
				$message = $message . "\n\n" . $data[2];
			}

			shell_exec('git commit '
				. ' --date=' . escapeshellarg(gmdate('r', $data[1]))
				. ' --author=' . escapeshellarg("{$data[0]} <{$data[0]}@{$data[0]}>")
				. ' --message=' . escapeshellarg($message)
				);
		}
	}

	/**
	* Collect svn history
	*/
	protected function svnHistory()
	{
		echo " < Extracting SVN History...\n";
		$tmp = $this->svnHistoryTmp();

		shell_exec('svn log ' . escapeshellarg($this->source) . ' > ' . $tmp);
		echo " < Done.\n";
		$this->svnHistoryParse($tmp);

		echo " < SVN History: " . count($this->history) . " revisions\n\n";
	}

	/**
	* Return tmp file to record the svn history
	* @return string
	*/
	protected function svnHistoryTmp()
	{
		$last = shell_exec('svn log -l 1 ' . escapeshellarg($this->source) );
		return '/tmp/haremasu.' . md5( $this->svn_url . $last ) . '.svn.tmp';
	}

	/**
	* Parse the file with the svn history
	* @param string $tmp
	*/
	protected function svnHistoryParse($tmp)
	{
		if (!($t = fopen($tmp, 'r')))
		{
			$this->error("not able to open {$tmp}\n");
		}

		$_ = '------------------------------------------------------------------------' . "\n";

		$commit = '';
		while ($s = fgets($t))
		{
			if ($_ == $s)
			{
				if ($commit)
				{
					$this->svnHistoryCommit($commit);
				}

				continue;
			}

			$commit .= $s;
		}

		fclose($t);

		// delete the file at the end of the script
		//
		register_shutdown_function('unlink', $tmp);
	}

	/**
	* Parse svn commit to extract revision, author, date and message
	* @param string $commit
	*/
	protected function svnHistoryCommit(&$commit)
	{
		if (!preg_match('~r(\d+) \| (\w+) \| ([^)]+) \([^)]+\) \| \d line.*\s~', $commit, $R))
		{
			return false;
		}

		// the revision number is the array key, the element
		// has the author, the date (in unix timestamp format),
		// and the message
		//
		$this->history [ $R[1] ] = array(
			$R[2],
			strtotime($R[3]),
			trim(str_replace($R[0], '', $commit))
			);

		$commit = '';
		return true;
	}
}
