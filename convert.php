<?php
	if (!file_exists('config.json')) {
		echo "You must provide a config.json file, checkout config.json.example.";
		return;
	}

	$config = json_decode(file_get_contents('config.json'));

	$org = $config->org;
	$user = $config->username;
	$password = $config->password;

	// Read JSON file
	$json = exec("curl --user " . $user . ":'" . $password . "' 'https://api.bitbucket.org/2.0/repositories/" . $org . "?page=1&pagelen=100'");

	if (!$json) {
		echo "Failed to get repositories using the Bitbucket API\r\n";
		return;
	}

	//Decode JSON
	$repositories = json_decode($json)->values;

	$repo_mapping = [];
	foreach ($repositories as $repository) {
		if ($repository->scm == 'hg') {
			$username = $repository->owner->username;
			foreach ($repository->links->clone as $clone) {
				if ($clone->name == 'ssh') {
					echo "Found " . $repository->full_name . "\r\n";

					$dir = "hg/" . $repository->slug;
					if (!file_exists($dir)) {
						exec('hg clone ' . $clone->href . ' ' . $dir) . "\r\n";

						// Had issues with symlinks here, maybe not a good idea
						exec('rm -rf ' . $dir . '/.hgcheck');

						// Remove symlinks
						exec('find ' . $dir . ' -type l -delete');
						exec('cd ' . $dir . ' && hg remove -A && hg commit -m "remove symlinks" && hg push');
					}
					$repo_mapping[$dir] = 'git/' . $repository->slug;

					$data->scm = "git";
					$data->is_private = $repository->is_private;
					$data->fork_policy = $repository->fork_policy;
					$data->website = $repository->website;
					$data->description = $repository->description;
					$data->name = $repository->name . "-git";
					$data->language = $repository->language;
					$data->project = $arrayName = array('key' => $repository->project->key);

					echo "curl -X POST -H \"Content-Type: application/json\" -d '" . json_encode($data) . "' --user " . $user . ":'" . $password . "' 'https://api.bitbucket.org/2.0/repositories/" . $org . "/" . $repository->slug . "-git'" . "\r\n";

					// Create git repo
					exec("curl -X POST -H \"Content-Type: application/json\" -d '" . json_encode($data) . "' --user " . $user . ":'" . $password . "' 'https://api.bitbucket.org/2.0/repositories/" . $org . "/" . $repository->slug . "-git'");
				}
			}
		}
	}
	return;
	$output = str_replace("\/", "/", json_encode($repo_mapping));

	file_put_contents("repo_mapping.json", $output);

	if (!file_exists('authors.map')) {
		exec('python hg-export-tool/list-authors.py repo_mapping.json');
	}

	exec('python hg-export-tool/exporter.py repo_mapping.json -A authors.map', $output, $return);

	if ($return != 0) {
		echo "hg-export-tool failed, check output";
		return;
	}

	$dirs = array_filter(glob('./git/*'), 'is_dir');
	foreach ($dirs as $dir) {
		$slug = str_replace('./git/', '', $dir);

		// Add remote for git $repositories
		exec("cd git/" . $slug . " && git remote add origin git@bitbucket.org:" . $org . "/" . $slug . "-git.git");

		// Push master
		//exec("cd git/" . $slug . " && git push -u origin master");

		// Push tags
		//exec("cd git/" . $slug . " && git push --tags origin");
	}
?>
