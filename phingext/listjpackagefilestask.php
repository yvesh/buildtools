<?php
/**
 * @package    Buildtools
 * @author     DanielDimitrov <daniel@compojoom.com>
 * @date       02.11.13
 *
 * @copyright  Copyright (C) 2008 - 2013 compojoom.com . All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

require_once "phing/Task.php";

/**
 * Class listJPackageFilesTask
 *
 * This class reads the content of a folder and output its content with the
 * required syntax for joomla extension, component, plugin
 *
 * @since  1.0
 */
class ListJPackageFilesTask extends Task
{
	/**
	 * The xml file for the extension
	 *
	 * @var
	 */
	public $file;

	/**
	 * The source directory path
	 *
	 * @var
	 */
	public $sourceDir;

	/**
	 * The component name
	 *
	 * @var
	 */
	public $component;

	/**
	 * Sets the file name
	 *
	 * @param   string  $str  - path to the xml file
	 *
	 * @return void
	 */
	public function setFile($str)
	{
		$this->file = $str;
	}

	/**
	 * Sets the source directory
	 *
	 * @param   string  $dir  - path to the source directory
	 *
	 * @return void
	 */
	public function setSourceDir($dir)
	{
		$this->sourceDir = $dir;
	}

	/**
	 * Sets the component name
	 *
	 * @param   string  $name  - the component name
	 *
	 * @return void
	 */
	public function setComponent($name)
	{
		$this->component = $name;
	}

	/**
	 * The init method: Do init steps.
	 *
	 * @return void
	 */
	public function init()
	{
		// Nothing to do here
	}

	/**
	 * This function reads the content of the .xml file and determines what files to search for
	 * Then it writes them back in the placeholder location of the .xml file
	 *
	 * @return void
	 */
	public function main()
	{
		$content = file_get_contents($this->file);

		$content = preg_replace_callback('/##PACKAGEFILESPLUGIN##/', 'self::findPluginPackageFiles', $content);

		if (preg_match('/##PACKAGEFILESMODULE##/', $content))
		{
			$content = preg_replace(
				'/##PACKAGEFILESMODULE##/',
				call_user_func('self::findModulePackageFiles'), $content
			);
		}

		if (preg_match('/##ADMINLANGUAGEFILES##/', $content))
		{
			$content = preg_replace(
				'/##ADMINLANGUAGEFILES##/',
				call_user_func('self::languageFiles', true), $content
			);
		}

		if (preg_match('/##FRONTENDLANGUAGEFILES##/', $content))
		{
			$content = preg_replace(
				'/##FRONTENDLANGUAGEFILES##/',
				call_user_func('self::languageFiles', false), $content
			);
		}

		if (preg_match('/##ADMINCOMPONENTPACKAGEFILES##/', $content))
		{
			$content = preg_replace(
				'/##ADMINCOMPONENTPACKAGEFILES##/',
				call_user_func('self::findComponentPackagefiles', true), $content
			);
		}

		if (preg_match('/##FRONTENDCOMPONENTPACKAGEFILES##/', $content))
		{
			$content = preg_replace(
				'/##FRONTENDCOMPONENTPACKAGEFILES##/',
				call_user_func('self::findComponentPackagefiles', false), $content
			);
		}

		if (preg_match('/##MEDIAPACKAGEFILES##/', $content))
		{
			$content = preg_replace(
				'/##MEDIAPACKAGEFILES##/',
				call_user_func('self::findMediaPackagefiles', false), $content
			);
		}

		if (preg_match('/##LIBRARYFILES##/', $content))
		{
			$content = preg_replace(
				'/##LIBRARYFILES##/',
				call_user_func('self::findLibraryPackageFiles', false), $content
			);
		}

		file_put_contents($this->file, $content);
	}

	/**
	 * Lists the language files
	 *
	 * @param   bool  $admin  - determines whether we are dealing with backend files or not
	 *
	 * @return string
	 */
	public function languageFiles($admin = false)
	{
		$languageFolder = $this->sourceDir . '/language';

		if ($admin)
		{
			$languageFolder = $this->sourceDir . '/administrator/language';
		}

		$list = array();

		print $languageFolder;

		if (file_exists($languageFolder))
		{
			$dir = new DirectoryIterator($languageFolder);

			foreach ($dir as $element)
			{
				if (!$element->isDot())
				{
					if ($element->isDir())
					{
						$langDir = new DirectoryIterator($element->getPath() . '/' . $element->getFileName());

						foreach ($langDir as $langElement)
						{
							if (!$langElement->isDot())
							{
								if ($langElement->isFile())
								{
									if ($this->component)
									{
										$name = explode('.', $langElement->getFileName());
										$name = $name[1];

										if ($name == $this->component)
										{
											$list[] = '<language tag="' . $element->getFileName() . '">'
												. $element->getFileName() . '/' . $langElement->getFileName() . '</language>';
										}
									}
								}
							}
						}
					}
				}
			}
		}
		else
		{
			echo 'Folder ' . $languageFolder . ' doesn\'t exist';
		}

		return implode("\n", $list);
	}

	/**
	 * Finds the component package files
	 *
	 * @param   bool  $admin  - determines whether we are dealing with the backend or frontend
	 *
	 * @return string
	 */
	public function findComponentPackagefiles($admin = false)
	{
		$list = array();
		$componentFolder = $this->sourceDir . '/components/' . $this->component;

		if ($admin)
		{
			$componentFolder = $this->sourceDir . '/administrator/components/' . $this->component;
		}

		if (file_exists($componentFolder))
		{
			$dir = new DirectoryIterator($componentFolder);

			foreach ($dir as $element)
			{
				if (!$element->isDot())
				{
					if ($element->isDir())
					{
						$list[] = '<folder>' . $element->getFileName() . '</folder>';
					}

					if ($element->isFile())
					{
						$list[] = '<file>' . $element->getFileName() . '</file>';
					}
				}
			}
		}
		else
		{
			echo 'Folder ' . $componentFolder . ' doesn\'t exist';
		}

		return implode("\n", $list);
	}


	/**
	 * Finds the library package files
	 *
	 * @return string
	 */
	public function findLibraryPackageFiles()
	{
		$nameParts = explode('_', $this->component);
		$library = $nameParts[1];

		$path = $this->sourceDir . '/libraries/' . $library;
		$list = array();

		if (file_exists($path))
		{
			$dir = new DirectoryIterator($path);

			foreach ($dir as $element)
			{
				if (!$element->isDot())
				{
					if ($element->isDir())
					{
						$list[] = '<folder>' . $element->getFileName() . '</folder>';
					}

					if ($element->isFile())
					{
						$list[] = '<file>' . $element->getFileName() . '</file>';
					}
				}
			}
		}
		else
		{
			echo 'Folder for library' . $library . ' doesn\'t exist';
		}

		return implode("\n", $list);
	}

	/**
	 * List the media package files
	 *
	 * @return string
	 */
	public function findMediaPackagefiles()
	{
		$list = array();
		$source = $this->sourceDir;
		$mediaFolder = $source . '/media/' . $this->component;

		if (file_exists($mediaFolder))
		{
			$dir = new DirectoryIterator($mediaFolder);

			foreach ($dir as $element)
			{
				if (!$element->isDot() && substr($element, 0, 1) != ".")
				{
					if ($element->isDir())
					{
						$list[] = '<folder>' . $element->getFileName() . '</folder>';
					}

					if ($element->isFile())
					{
						$list[] = '<file>' . $element->getFileName() . '</file>';
					}
				}
			}
		}
		else
		{
			echo 'Folder ' . $mediaFolder . ' doesn\'t exist';
		}

		return implode("\n", $list);
	}

	/**
	 * Finds the plugin files
	 *
	 * @return string
	 */
	public function findPluginPackageFiles()
	{
		$list = array();

		if (file_exists($this->sourceDir))
		{
			$dir = new DirectoryIterator($this->sourceDir);

			foreach ($dir as $element)
			{
				if (!$element->isDot())
				{
					if ($element->isDir())
					{
						$skip = false;

						if ($element->getFileName() == 'administrator')
						{
							/**
							 * we need to handle the language folder in the plugin
							 * differently. If the administrator folder contains
							 * just the language folder we don't need to list it.
							 * Otherwise when the user installs the plugin he will have
							 * administrator/language in his plugi folder which is lame...
							 */
							$adminDir = new DirectoryIterator($this->sourceDir . '/administrator');
							$i = 0;
							$language = false;

							foreach ($adminDir as $adminElement)
							{
								if ($adminElement->isDir() && !$adminElement->isDot())
								{
									if ($adminElement->getFileName() == 'language')
									{
										$language = true;
									}

									$i++;
								}
							}

							/**
							 * so we have just one folder and it is
							 * the language one???
							 */
							if ($i == 1 && $language == true)
							{
								$skip = true;
							}
						}

						if (!$skip)
						{
							$list[] = '<folder>' . $element->getFileName() . '</folder>';
						}
					}

					if ($element->isFile())
					{
						$packageMainFile = basename($this->file, '.xml');

						if ($element->getFileName() == $packageMainFile . '.php')
						{
							$list[] = '<file plugin="' . $packageMainFile . '">' . $element->getFilename() . '</file>';
						}
						elseif ($element->getFileName() != basename($this->file))
						{
							$list[] = '<file>' . $element->getFileName() . '</file>';
						}
					}
				}
			}
		}
		else
		{
			echo 'Folder ' . $this->sourceDir . ' doesn\'t exist';
		}

		return implode("\n", $list);
	}

	/**
	 * List the module package files
	 *
	 * @return string
	 */
	public function findModulePackageFiles()
	{
		$list = array();

		if (file_exists($this->sourceDir))
		{
			$dir = new DirectoryIterator($this->sourceDir);

			foreach ($dir as $element)
			{
				if (!$element->isDot())
				{
					if ($element->isDir())
					{
						$skip = false;

						if ($element->getFileName() == 'administrator')
						{
							/**
							 * we need to handle the language folder in the plugin
							 * differently. If the administrator folder contains
							 * just the language folder we don't need to list it.
							 * Otherwise when the user installs the plugin he will have
							 * administrator/language in his plugi folder which is lame...
							 */
							$adminDir = new DirectoryIterator($this->sourceDir . '/administrator');
							$i = 0;
							$language = false;

							foreach ($adminDir as $adminElement)
							{
								if ($adminElement->isDir() && !$adminElement->isDot())
								{
									if ($adminElement->getFileName() == 'language')
									{
										$language = true;
									}

									$i++;
								}
							}

							/**
							 * so we have just one folder and it is
							 * the language one???
							 */
							if ($i == 1 && $language == true)
							{
								$skip = true;
							}
						}

						if (!$skip)
						{
							$list[] = '<folder>' . $element->getFileName() . '</folder>';
						}
					}

					if ($element->isFile())
					{
						$packageMainFile = basename($this->file, '.xml');

						if ($element->getFileName() == $packageMainFile . '.php')
						{
							$list[] = '<file module="' . $packageMainFile . '">' . $element->getFilename() . '</file>';
						}
						elseif ($element->getFileName() != basename($this->file))
						{
							$list[] = '<file>' . $element->getFileName() . '</file>';
						}
					}
				}
			}
		}
		else
		{
			echo 'Folder ' . $this->sourceDir . ' doesn\'t exist';
		}

		return implode("\n", $list);
	}
}
