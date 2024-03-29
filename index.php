<?php
/**
 * gifs.benlk.com
 *
 * This project:
 * 1. reads in a list of files from the present directory
 * 2. reads in ./sources.csv
 * 3. outputs a templated structure listing all files,
 *    with description information.
 */

require 'vendor/autoload.php';
use League\Csv\Reader;
use Cocur\Slugify\Slugify;

/**
 * Return an array of files matching specific types in this directory
 */
function get_files() {
	return array_reverse( glob( "*.{pdf}", GLOB_BRACE ) );
}

/**
 * Return the contents of the CSV as parsed by leavue/CSV
 *
 * @link https://csv.thephpleague.com/9.0/reader/
 */
function get_csv() {
	$csv = Reader::createFromPath( './sources.csv', 'r' );
	$csv->setHeaderOffset(0);
	return $csv;
}

/**
 * Merge the CSV and the file list
 *
 * @param Iterator $csv   The result of Reader::getRecords
 * @param array    $files The list of files with specific filetypes in this folder, as a simple array of file names
 * @return array of filename => csv row
 */
function merge_list( $csv, $files ) {
	// $files is an array of index => filename
	// let's flip it to make search faster
	$merged = array_flip( $files );

	foreach ( $csv as $offset => $record ) {
		if ( isset( $merged[ $record['filename'] ] ) ) {
			$merged[ $record['filename'] ] = $record;
		} else {
			$merged[] = $record;
		}
	}

	return $merged;
}

/**
 * Convert number of bytes to human-readable units
 *
 * Derives from Jeffrey Sambells' function, which used
 * power-of-ten abbreviations for power-of-two counts.
 * This fixes that by counting bytes by power-of-ten,
 * and returns strings with power-of-ten abbreviations.
 *
 * @link https://web.archive.org/web/20191219095113/http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
 * @param int $bytes    Bytes in the file
 * @param int $decimals Decimals of precision
 * @return String
 */
function human_filesize($bytes, $decimals = 2) {
	$size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1000, $factor)) . @$size[$factor];
}

/**
 * Create the table of things
 */
function render_table() {
	$csv = get_csv();
	$files = get_files();
	$merged = merge_list( $csv, $files );
	$headers = $csv->getHeader();
	$slugify = new Slugify();

	echo '<table>';
		echo '<thead>';
			echo '<tr>';
				foreach ( $headers as $header ) {
					printf(
						'<th scope="col" class="%1$s">%2$s</th>',
						$slugify->slugify( $header ),
						htmlspecialchars( $header )
					);
				}
				echo '<th scope="col">filesize</th>';
			echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
			foreach ( $merged as $filename => $data ) {
				echo '<tr>';

					foreach ( $headers as $header ) {
						switch ($header) {
							case 'filename':
								printf(
									'<th scope="row" class="filename"><a href="%1$s">%2$s</a></th>',
									rawurlencode( $filename ),
									htmlspecialchars( $filename )
								);
								break;
							case 'description':
								$description = $data[ $header ] ? htmlspecialchars( $data[ $header ] ) : '&#9888;&#xfe0f; This file is not described in sources.csv';
								printf(
									'<td class="%1$s">%2$s</td>',
									$slugify->slugify( $header ),
									$description
								);
								break;
							case 'blog_url':
								if ( ! empty( $data[ $header ] ) ) {
									printf(
										'<td class="%1$s"><a href="%2$s">%3$s</a></td>',
										$slugify->slugify( $header ),
										$data[ $header ],
										'&#128279;&#xFE0E;' // variation selector 15 to force non-emoji
									);
								} else {
									printf(
										'<td class="%1$s"></td>',
										$slugify->slugify( $header )
									);
								}
								break;
							default:
								printf(
									'<td class="%1$s">%2$s</td>',
									$slugify->slugify( $header ),
									htmlspecialchars( $data[ $header ] )
								);
								break;
						}
					}
					printf(
						'<td class="filesize">%1$s</td>',
						htmlspecialchars( human_filesize( filesize( $filename ), 0 ) )
					);

				echo '</tr>';
			}
		echo '</tbody>';
	echo '</table>';
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Zoning Report List Page</title>
		<style type="text/css">
			* {
				box-sizing: border-box;
				font-family: sans-serif;
				font-size: 14px;
			}
			table {
				max-width: 100%;
				overflow-x: scroll;
				border-collapse: collapse;
			}
			thead {
				border: 2px solid black;
				position: sticky;
				top: 0;
				background-color: white;
			}
			thead td {
				border-bottom: 1px solid black;
			}
			tbody tr {
				border-top: 1px solid #ddd;
			}
			th, td {
				vertical-align: top;
			}
			th {
				text-align: left;
			}
			td,
			tbody th {
				font-weight: normal;
				padding-bottom: 1.0em;
			}
			td.source-url {
				max-width: 10ch;
				overflow: hidden;
				max-height: 1em;
				font-family: "Symbola", "Segoe UI Symbol", sans-serif;
			}
		</style>
	</head>
	<body>
		<h1>North Linden Area Commission Zoning Reports</h1>
		<p>A list of zoning reports prepared by Ben Keith for the <a href="https://cbusareacommissions.org/north-linden/">North Linden Area Commission</a>'s zoning committee.</p>
		<p>Beginning in September 2023, agendas for meetings are posted to the <a href="https://us21.campaign-archive.com/home/?u=330bc1a667408c7b6f03e46bb&id=b4deb59789">zoning committee newsletter</a>.</p>

		<?php
			render_table();
		?>

		<footer>
			<p>
				This page is <a href="https://github.com/benlk/nlac-zoning-index">powered by open-source software.</a>
			</p>
		</footer>
	</body>
</html>
</html>
