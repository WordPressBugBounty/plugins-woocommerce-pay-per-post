<?php
/**
 * The documentation, shown on Need Help > Docs.
 *
 * The pages are the Markdown files in docs/, shipped with the plugin, so the
 * docs a site owner reads always match the version they run. docs/README.md
 * is the index: its sections and lists give the order and grouping.
 *
 * The renderer understands the Markdown the docs use and nothing more:
 * headings, paragraphs, bullet and numbered lists one level deep, tables,
 * fenced code, quotes, inline code, bold, italics and links. Everything is
 * escaped first, so a page can never put markup on the screen.
 *
 * A page may name a shorter title for the docs menu on the line after its
 * title, as an HTML comment, which GitLab and the renderer both hide:
 *
 *     # Protect a post, page or custom post type
 *     <!-- Short title: Protect a post or page -->
 *
 * Pages refer to each other by title, in quotes ("Where protection applies")
 * or as "the Hooks reference". Those become links here, so the files read
 * the same on GitLab, in a text editor and in wp-admin.
 *
 * tests/integration/DocsTest.php renders every page.
 *
 * @package Woocommerce_pay_per_post
 */

class Woocommerce_Pay_Per_Post_Docs {

	/** The index page's file name, without .md. */
	const INDEX = 'README';

	/** Characters in a search result's excerpt. */
	const EXCERPT_LENGTH = 180;

	/** @var array<string, string>|null Slug => title. */
	private static $pages = null;

	/** @var array<string, string> Slug => short title, for pages that name one. */
	private static $short = [];

	/** @var array<int, array{id: string, title: string}> The rendered page's sections. */
	private static $toc = [];

	/** @var string The page being rendered; it does not link to itself. */
	private static $current = '';

	/** @var string[] Inline code and links, set aside while text is formatted. */
	private static $held = [];

	/** @var array<string, int> Heading ids used on the page being rendered. */
	private static $ids = [];

	/**
	 * The docs folder.
	 *
	 * @return string With a trailing slash.
	 */
	public static function dir(): string {
		return plugin_dir_path( dirname( __FILE__ ) ) . 'docs/';
	}

	/**
	 * Every page but the index, by slug: the path inside docs/ without .md,
	 * such as how-to/protect-a-post. Reads each file's first lines only: its
	 * title, and the short title if it names one.
	 *
	 * @return array<string, string> Slug => title.
	 */
	public static function pages(): array {
		if ( null !== self::$pages ) {
			return self::$pages;
		}

		$pages = [];
		$dir   = self::dir();
		$files = array_merge( (array) glob( $dir . '*.md' ), (array) glob( $dir . '*/*.md' ) );

		foreach ( $files as $file ) {
			$slug = substr( (string) $file, strlen( $dir ), -3 );
			if ( self::INDEX === $slug || ! preg_match( '#^[a-z0-9-]+(/[a-z0-9-]+)?$#', $slug ) ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$handle = fopen( $file, 'r' );
			$first  = $handle ? (string) fgets( $handle ) : '';
			$second = $handle ? (string) fgets( $handle ) : '';
			if ( $handle ) {
				fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}

			$pages[ $slug ] = preg_match( '/^#\s+(.+)$/', trim( $first ), $match ) ? trim( $match[1] ) : $slug;

			if ( preg_match( '/^<!--\s*Short title:\s*(.+?)\s*-->$/i', trim( $second ), $match ) ) {
				self::$short[ $slug ] = $match[1];
			}
		}

		ksort( $pages );
		self::$pages = $pages;

		return $pages;
	}

	/**
	 * A page's title for the docs menu: its short title, or its title.
	 *
	 * @param string $slug A key of pages().
	 *
	 * @return string
	 */
	public static function short_title( string $slug ): string {
		$pages = self::pages();

		return self::$short[ $slug ] ?? ( $pages[ $slug ] ?? '' );
	}

	/**
	 * Where a page sits in the index, for its breadcrumb.
	 *
	 * @param string $slug A key of pages().
	 *
	 * @return array{section: string, group: string}|null Null for a page the index does not list.
	 */
	public static function location( string $slug ): ?array {
		foreach ( self::sections() as $section ) {
			foreach ( $section['groups'] as $group ) {
				foreach ( $group['links'] as $link ) {
					if ( $link['slug'] === $slug ) {
						return [
							'section' => $section['title'],
							'group'   => $group['label'],
						];
					}
				}
			}
		}

		return null;
	}

	/**
	 * The index's sections, each with its pages in order.
	 *
	 * @return array<int, array{title: string, intro: string, groups: array<int, array{label: string, links: array<int, array{slug: string, title: string, note: string, premium: bool}>}>}>
	 */
	public static function sections(): array {
		$sections = [];
		$section  = null;
		$intro    = true;

		foreach ( self::lines( self::INDEX ) as $line ) {
			if ( preg_match( '/^##\s+(.+)$/', $line, $match ) ) {
				if ( $section ) {
					$sections[] = $section;
				}
				$section = [
					'title'  => trim( $match[1] ),
					'intro'  => '',
					'groups' => [],
				];
				$intro   = true;
				continue;
			}

			if ( ! $section || '' === trim( $line ) ) {
				continue;
			}

			if ( preg_match( '/^[-*]\s+(.+)$/', $line, $match ) ) {
				$link = self::index_entry( $match[1] );
				if ( $link ) {
					if ( ! $section['groups'] ) {
						$section['groups'][] = [
							'label' => '',
							'links' => [],
						];
					}
					$section['groups'][ count( $section['groups'] ) - 1 ]['links'][] = $link;
				}
				$intro = false;
			} elseif ( ':' === substr( rtrim( $line ), -1 ) ) {
				$section['groups'][] = [
					'label' => rtrim( trim( $line ), ':' ),
					'links' => [],
				];
				$intro               = false;
			} elseif ( $intro ) {
				$section['intro'] .= ( '' === $section['intro'] ? '' : ' ' ) . trim( $line );
			}
		}

		if ( $section ) {
			$sections[] = $section;
		}

		return array_values(
			array_filter(
				$sections,
				static function ( $section ) {
					foreach ( $section['groups'] as $group ) {
						if ( $group['links'] ) {
							return true;
						}
					}
					return false;
				}
			)
		);
	}

	/**
	 * The index's opening paragraph, after its title.
	 *
	 * @return string HTML.
	 */
	public static function intro(): string {
		$paragraphs = [];
		foreach ( self::lines( self::INDEX ) as $line ) {
			if ( 0 === strpos( $line, '## ' ) ) {
				break;
			}
			if ( '' !== trim( $line ) && 0 !== strpos( $line, '# ' ) ) {
				$paragraphs[] = '<p>' . self::inline( trim( $line ) ) . '</p>';
			}
		}

		return implode( "\n", $paragraphs );
	}

	/**
	 * Pages in the index's order, for the previous and next links.
	 *
	 * @return string[] Slugs.
	 */
	public static function order(): array {
		$order = [];
		foreach ( self::sections() as $section ) {
			foreach ( $section['groups'] as $group ) {
				foreach ( $group['links'] as $link ) {
					$order[] = $link['slug'];
				}
			}
		}

		return array_values( array_unique( $order ) );
	}

	/**
	 * One page, rendered.
	 *
	 * @param string $slug From the request; anything that is not a page is refused.
	 *
	 * @return array{slug: string, title: string, html: string, toc: array<int, array{id: string, title: string}>, prev: string, next: string}|null
	 *         toc is the page's sections (its second-level headings), for "On this page".
	 */
	public static function page( string $slug ): ?array {
		$pages = self::pages();
		if ( ! isset( $pages[ $slug ] ) ) {
			return null;
		}

		$order    = self::order();
		$position = array_search( $slug, $order, true );
		$html     = self::render( implode( "\n", self::lines( $slug ) ), $slug );

		return [
			'slug'  => $slug,
			'title' => $pages[ $slug ],
			'html'  => $html,
			'toc'   => self::$toc,
			'prev'  => false !== $position && $position > 0 ? $order[ $position - 1 ] : '',
			'next'  => false !== $position && isset( $order[ $position + 1 ] ) ? $order[ $position + 1 ] : '',
		];
	}

	/**
	 * Pages containing every word of the query, title matches first.
	 *
	 * Reads every page, about 180 KB in all: a fixed cost that does not
	 * depend on the store.
	 *
	 * @param string $query
	 *
	 * @return array<int, array{slug: string, title: string, excerpt: string}> The excerpt is
	 *         HTML: escaped, with the words searched for marked.
	 */
	public static function search( string $query ): array {
		$words = array_values( array_filter( preg_split( '/\s+/', strtolower( trim( $query ) ) ) ) );
		if ( ! $words ) {
			return [];
		}

		$results = [];
		foreach ( self::pages() as $slug => $title ) {
			$text = self::plain_text( implode( "\n", self::lines( $slug ) ) );
			$hay  = strtolower( $title . ' ' . $text );

			foreach ( $words as $word ) {
				if ( false === strpos( $hay, $word ) ) {
					continue 2;
				}
			}

			$in_title = 0;
			foreach ( $words as $word ) {
				$in_title += false !== strpos( strtolower( $title ), $word ) ? 1 : 0;
			}

			$results[] = [
				'slug'    => $slug,
				'title'   => $title,
				'excerpt' => self::excerpt( $text, strtolower( trim( $query ) ), $words ),
				'score'   => $in_title,
			];
		}

		usort(
			$results,
			static function ( $a, $b ) {
				$order = $b['score'] <=> $a['score'];

				return 0 !== $order ? $order : strcmp( $a['title'], $b['title'] );
			}
		);

		return array_map(
			static function ( $result ) {
				unset( $result['score'] );
				return $result;
			},
			$results
		);
	}

	/**
	 * A page's address on Need Help > Docs.
	 *
	 * @param string $slug Empty for the index.
	 *
	 * @return string
	 */
	public static function url( string $slug = '' ): string {
		$url = admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-support&view=docs' );

		return '' === $slug ? $url : $url . '&doc=' . rawurlencode( $slug );
	}

	/**
	 * Markdown to HTML.
	 *
	 * @param string $markdown
	 * @param string $current The page being rendered, which does not link to itself.
	 *
	 * @return string
	 */
	public static function render( string $markdown, string $current = '' ): string {
		self::$current = $current;
		self::$ids     = [];
		self::$toc     = [];

		return self::blocks( explode( "\n", str_replace( [ "\r\n", "\r" ], "\n", $markdown ) ) );
	}

	/**
	 * A page's lines.
	 *
	 * @param string $slug A key of pages(), or the index.
	 *
	 * @return string[]
	 */
	private static function lines( string $slug ): array {
		$file = self::dir() . $slug . '.md';
		if ( ! is_readable( $file ) ) {
			return [];
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return explode( "\n", str_replace( [ "\r\n", "\r" ], "\n", (string) file_get_contents( $file ) ) );
	}

	/**
	 * An index list item as a link: "Troubleshooting: symptoms and fixes."
	 * names the page before the colon, and "(Premium)" becomes a flag.
	 *
	 * @param string $text
	 *
	 * @return array{slug: string, title: string, note: string, premium: bool}|null
	 */
	private static function index_entry( string $text ): ?array {
		$text    = trim( $text );
		$premium = (bool) preg_match( '/\s*\(Premium\)\s*\.?$/', $text );
		$text    = trim( (string) preg_replace( '/\s*\(Premium\)\s*\.?$/', '', $text ) );
		$note    = '';

		$slug = self::find( $text );
		if ( '' === $slug && false !== strpos( $text, ':' ) ) {
			list( $name, $note ) = array_map( 'trim', explode( ':', $text, 2 ) );
			$slug                = self::find( $name );
		}

		if ( '' === $slug ) {
			return null;
		}

		return [
			'slug'    => $slug,
			'title'   => self::pages()[ $slug ],
			'note'    => rtrim( $note, '.' ),
			'premium' => $premium,
		];
	}

	/**
	 * The page with this title. Case, a closing full stop and a closing
	 * bracketed aside, as in "Requirements" or "Choose a protection type
	 * (Access Diagnostic)", do not matter.
	 *
	 * @param string $title
	 *
	 * @return string Slug, or '' when no page has that title.
	 */
	private static function find( string $title ): string {
		static $titles = null;

		if ( null === $titles ) {
			$titles = [];
			foreach ( self::pages() as $slug => $page_title ) {
				$titles[ self::normalise( $page_title ) ] = $slug;
				$short                                    = trim( (string) preg_replace( '/\s*\([^)]*\)$/', '', $page_title ) );
				if ( ! isset( $titles[ self::normalise( $short ) ] ) ) {
					$titles[ self::normalise( $short ) ] = $slug;
				}
			}
		}

		return $titles[ self::normalise( $title ) ] ?? '';
	}

	/**
	 * @param string $title
	 *
	 * @return string
	 */
	private static function normalise( string $title ): string {
		$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );

		return strtolower( trim( rtrim( trim( str_replace( [ '’', '&#039;' ], "'", $title ) ), '.' ) ) );
	}

	/**
	 * Lines to block-level HTML.
	 *
	 * @param string[] $lines
	 *
	 * @return string
	 */
	private static function blocks( array $lines ): string {
		$html  = [];
		$count = count( $lines );
		$i     = 0;

		while ( $i < $count ) {
			$line = $lines[ $i ];

			// Blank lines, and comments such as the short title.
			if ( '' === trim( $line ) || preg_match( '/^\s*<!--.*-->\s*$/', $line ) ) {
				++$i;
				continue;
			}

			// Fenced code.
			if ( preg_match( '/^\s*```/', $line ) ) {
				$code = [];
				++$i;
				while ( $i < $count && ! preg_match( '/^\s*```\s*$/', $lines[ $i ] ) ) {
					$code[] = $lines[ $i ];
					++$i;
				}
				++$i;
				$html[] = '<pre class="wcppp-code"><code>' . esc_html( implode( "\n", $code ) ) . '</code></pre>';
				continue;
			}

			// Headings.
			if ( preg_match( '/^(#{1,4})\s+(.+?)\s*#*$/', $line, $match ) ) {
				$level  = strlen( $match[1] );
				$slug   = $level > 1 ? self::heading_id( $match[2] ) : '';
				$id     = '' !== $slug ? ' id="' . esc_attr( $slug ) . '"' : '';
				$html[] = sprintf( '<h%1$d%2$s>%3$s</h%1$d>', $level, $id, self::inline( $match[2], false ) );
				if ( 2 === $level ) {
					self::$toc[] = [
						'id'    => $slug,
						'title' => wp_strip_all_tags( self::inline( $match[2], false ) ),
					];
				}
				++$i;
				continue;
			}

			// Tables: a row, then a separator row.
			if ( 0 === strpos( ltrim( $line ), '|' ) && isset( $lines[ $i + 1 ] ) && preg_match( '/^\s*\|?\s*:?-{3,}/', $lines[ $i + 1 ] ) ) {
				$head = self::cells( $line );
				$i   += 2;
				$rows = [];
				while ( $i < $count && 0 === strpos( ltrim( $lines[ $i ] ), '|' ) ) {
					$rows[] = self::cells( $lines[ $i ] );
					++$i;
				}
				$html[] = self::table( $head, $rows );
				continue;
			}

			// Quotes.
			if ( preg_match( '/^\s*>/', $line ) ) {
				$quoted = [];
				while ( $i < $count && preg_match( '/^\s*>\s?(.*)$/', $lines[ $i ], $match ) ) {
					$quoted[] = $match[1];
					++$i;
				}
				$html[] = '<blockquote>' . self::blocks( $quoted ) . '</blockquote>';
				continue;
			}

			// Lists.
			if ( self::list_marker( $line ) ) {
				$items = [];
				while ( $i < $count ) {
					$current = $lines[ $i ];
					if ( '' === trim( $current ) ) {
						// A blank line inside a list continues it only when the
						// list or the item carries on after it.
						$next = $lines[ $i + 1 ] ?? '';
						if ( self::list_marker( $next ) || preg_match( '/^\s{2,}\S/', $next ) ) {
							$items[] = '';
							++$i;
							continue;
						}
						break;
					}
					if ( ! self::list_marker( $current ) && ! preg_match( '/^\s{2,}\S/', $current ) && ! $items ) {
						break;
					}
					if ( ! self::list_marker( $current ) && ! preg_match( '/^\s/', $current ) ) {
						// A lazy continuation of the item's text.
						$items[] = '   ' . $current;
						++$i;
						continue;
					}
					$items[] = $current;
					++$i;
				}
				$html[] = self::list_html( $items );
				continue;
			}

			// Paragraphs run to a blank line or the start of another block.
			$text = [];
			while ( $i < $count && '' !== trim( $lines[ $i ] ) ) {
				$starts_block = preg_match( '/^\s*(```|#{1,4}\s|>)/', $lines[ $i ] ) || self::list_marker( $lines[ $i ] ) || 0 === strpos( ltrim( $lines[ $i ] ), '|' );
				if ( $text && $starts_block ) {
					break;
				}
				$text[] = trim( $lines[ $i ] );
				++$i;
			}
			$text = implode( ' ', $text );
			// "**Expected result:** ..." is what a reader checks a step against.
			$class  = 0 === strpos( $text, '**Expected result:**' ) ? ' class="wcppp-callout is-ok"' : '';
			$html[] = '<p' . $class . '>' . self::inline( $text ) . '</p>';
		}

		return implode( "\n", $html );
	}

	/**
	 * A list item's marker at the start of a top-level line.
	 *
	 * @param string $line
	 *
	 * @return string 'ul', 'ol', or '' for none.
	 */
	private static function list_marker( string $line ): string {
		if ( preg_match( '/^ ?[-*]\s+\S/', $line ) ) {
			return 'ul';
		}

		return preg_match( '/^ ?\d+\.\s+\S/', $line ) ? 'ol' : '';
	}

	/**
	 * One list, with nested lists and paragraphs inside its items.
	 *
	 * @param string[] $lines The list's lines, the first a top-level item.
	 *
	 * @return string
	 */
	private static function list_html( array $lines ): string {
		$type  = self::list_marker( $lines[0] );
		$start = 'ol' === $type && preg_match( '/^\s*(\d+)\./', $lines[0], $match ) ? (int) $match[1] : 1;
		$items = [];

		foreach ( $lines as $line ) {
			if ( self::list_marker( $line ) ) {
				$items[] = [ (string) preg_replace( '/^\s*(?:[-*]|\d+\.)\s+/', '', $line ) ];
			} elseif ( $items ) {
				// Indented lines belong to the item: dedent them by up to four
				// spaces so a nested list starts at the left.
				$items[ count( $items ) - 1 ][] = (string) preg_replace( '/^ {1,4}/', '', $line );
			}
		}

		$html = [];
		foreach ( $items as $item ) {
			$body = self::blocks( $item );
			// A one-paragraph item, the usual case, needs no <p>.
			if ( preg_match( '#^<p>((?:(?!</p>).)*)</p>(.*)$#s', $body, $match ) ) {
				$body = $match[1] . $match[2];
			}
			$html[] = '<li>' . $body . '</li>';
		}

		$attrs = 'ol' === $type && 1 !== $start ? ' start="' . $start . '"' : '';

		return '<' . $type . $attrs . '>' . implode( '', $html ) . '</' . $type . '>';
	}

	/**
	 * A table row's cells. A pipe inside inline code or escaped with a
	 * backslash is part of the cell.
	 *
	 * @param string $line
	 *
	 * @return string[]
	 */
	private static function cells( string $line ): array {
		$line  = trim( $line );
		$line  = '|' === substr( $line, 0, 1 ) ? substr( $line, 1 ) : $line;
		$line  = '|' === substr( $line, -1 ) && '\\|' !== substr( $line, -2 ) ? substr( $line, 0, -1 ) : $line;
		$cells = [];
		$cell  = '';
		$code  = false;
		$len   = strlen( $line );

		for ( $i = 0; $i < $len; $i++ ) {
			$char = $line[ $i ];
			if ( '\\' === $char && '|' === ( $line[ $i + 1 ] ?? '' ) ) {
				$cell .= '|';
				++$i;
				continue;
			}
			if ( '`' === $char ) {
				$code = ! $code;
			}
			if ( '|' === $char && ! $code ) {
				$cells[] = trim( $cell );
				$cell    = '';
				continue;
			}
			$cell .= $char;
		}
		$cells[] = trim( $cell );

		return $cells;
	}

	/**
	 * @param string[]   $head
	 * @param string[][] $rows
	 *
	 * @return string
	 */
	private static function table( array $head, array $rows ): string {
		$html = '<div class="wcppp-table-wrap"><table class="wcppp-table wcppp-doc-table"><thead><tr>';
		foreach ( $head as $cell ) {
			$html .= '<th scope="col">' . self::inline( $cell ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$html .= '<tr>';
			foreach ( $head as $index => $unused ) {
				$html .= '<td>' . self::inline( $row[ $index ] ?? '' ) . '</td>';
			}
			$html .= '</tr>';
		}

		return $html . '</tbody></table></div>';
	}

	/**
	 * A heading's id, unique on the page.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	private static function heading_id( string $text ): string {
		$id = sanitize_title( str_replace( '`', '', $text ) );
		$id = '' === $id ? 'section' : $id;

		self::$ids[ $id ] = ( self::$ids[ $id ] ?? 0 ) + 1;

		return 1 === self::$ids[ $id ] ? $id : $id . '-' . self::$ids[ $id ];
	}

	/**
	 * Inline Markdown to HTML. Code spans are set aside before anything else,
	 * so nothing inside them is formatted or linked.
	 *
	 * @param string $text
	 * @param bool   $link_titles Link page titles named in the text.
	 *
	 * @return string
	 */
	private static function inline( string $text, bool $link_titles = true ): string {
		self::$held = [];

		$text = (string) preg_replace_callback(
			'/`([^`]+)`/',
			static function ( $match ) {
				return self::hold( '<code>' . esc_html( $match[1] ) . '</code>' );
			},
			$text
		);

		// Links: web addresses, and other pages by file name.
		$text = (string) preg_replace_callback(
			'/\[([^\]]+)\]\(([^)\s]+)\)/',
			static function ( $match ) {
				$href = $match[2];
				$slug = self::slug_from_href( $href );
				if ( '' !== $slug ) {
					return self::hold( '<a href="' . esc_url( self::url( $slug ) ) . '">' . esc_html( $match[1] ) . '</a>' );
				}
				if ( preg_match( '#^https?://#', $href ) ) {
					return self::hold( '<a href="' . esc_url( $href ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $match[1] ) . '</a>' );
				}
				return $match[1];
			},
			$text
		);

		$text = esc_html( $text );
		$text = (string) preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
		$text = (string) preg_replace( '/(?<![*\w])\*(?![\s*])(.+?)(?<![\s*])\*(?![*\w])/', '<em>$1</em>', $text );

		if ( $link_titles ) {
			$text = self::link_titles( $text );
		}

		return strtr( $text, self::$held );
	}

	/**
	 * Set HTML aside until the text around it is formatted.
	 *
	 * @param string $html
	 *
	 * @return string A placeholder.
	 */
	private static function hold( string $html ): string {
		$key                = "\u{E000}" . count( self::$held ) . "\u{E000}";
		self::$held[ $key ] = $html;

		return $key;
	}

	/**
	 * Link the pages a text names: a title in quotes, or "the Hooks reference".
	 *
	 * @param string $html Escaped text.
	 *
	 * @return string
	 */
	private static function link_titles( string $html ): string {
		$html = (string) preg_replace_callback(
			'/&quot;((?:(?!&quot;).){3,140}?)&quot;/',
			static function ( $match ) {
				$slug = self::find( $match[1] );
				if ( '' === $slug || $slug === self::$current ) {
					return $match[0];
				}
				return self::hold( '<a href="' . esc_url( self::url( $slug ) ) . '">' . $match[0] . '</a>' );
			},
			$html
		);

		return (string) preg_replace_callback(
			'/\b([A-Z][A-Za-z]*(?: [a-z]+)? reference)\b/',
			static function ( $match ) {
				$slug = self::find( $match[1] );
				if ( '' === $slug || $slug === self::$current ) {
					return $match[0];
				}
				return self::hold( '<a href="' . esc_url( self::url( $slug ) ) . '">' . $match[0] . '</a>' );
			},
			$html
		);
	}

	/**
	 * The page a relative link to a .md file points at.
	 *
	 * @param string $href
	 *
	 * @return string Slug, or '' when it is not a page.
	 */
	private static function slug_from_href( string $href ): string {
		if ( ! preg_match( '#^(?:\./|\.\./)*([a-z0-9/-]+)\.md(?:\#.*)?$#', $href, $match ) ) {
			return '';
		}

		foreach ( array_keys( self::pages() ) as $slug ) {
			if ( $slug === $match[1] || substr( $slug, -strlen( '/' . $match[1] ) ) === '/' . $match[1] ) {
				return $slug;
			}
		}

		return '';
	}

	/**
	 * The text around the first match, whole words only, with the words
	 * searched for marked.
	 *
	 * @param string   $text   Plain text.
	 * @param string   $phrase The query, lowercased.
	 * @param string[] $words  Its words, lowercased.
	 *
	 * @return string HTML.
	 */
	private static function excerpt( string $text, string $phrase, array $words ): string {
		$lower = strtolower( $text );
		$at    = strpos( $lower, $phrase );
		$at    = false === $at ? strpos( $lower, $words[0] ) : $at;
		$at    = false === $at ? 0 : $at;

		// Start at the beginning of a word about 60 characters earlier, and
		// end at the end of one.
		$space = $at > 60 ? strpos( $text, ' ', $at - 60 ) : false;
		$start = false === $space || $space >= $at ? ( $at > 60 ? $at : 0 ) : $space + 1;
		$end   = $start + self::EXCERPT_LENGTH;
		if ( $end < strlen( $text ) ) {
			$space = strrpos( substr( $text, 0, $end ), ' ' );
			$end   = false !== $space && $space > $at ? $space : $end;
		}

		$cut = substr( $text, $start, $end - $start );
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$cut = mb_convert_encoding( $cut, 'UTF-8', 'UTF-8' );
		}

		$html  = ( $start > 0 ? '… ' : '' ) . esc_html( trim( $cut ) ) . ( $end < strlen( $text ) ? ' …' : '' );
		$quote = implode(
			'|',
			array_map(
				static function ( $word ) {
					return preg_quote( esc_html( $word ), '/' );
				},
				$words
			)
		);

		// Mark words outside entities only.
		return (string) preg_replace( '/(?![^&;]+;)(' . $quote . ')/i', '<mark>$1</mark>', $html );
	}

	/**
	 * Markdown as plain text, for search.
	 *
	 * @param string $markdown
	 *
	 * @return string
	 */
	private static function plain_text( string $markdown ): string {
		$text = (string) preg_replace( '/^#\s+.+$/m', '', $markdown, 1 );
		$text = (string) preg_replace( '/^\s*<!--.*-->\s*$/m', '', $text );
		$text = (string) preg_replace( '/^\s*```.*$/m', '', $text );
		$text = (string) preg_replace( '/^\s*\|?\s*:?-{3,}.*$/m', '', $text );
		$text = (string) preg_replace( '/^#{2,4}\s+(.+?):?\s*$/m', '$1', $text );
		$text = (string) preg_replace( '/^\s*\|\s*(.*?)\s*\|?\s*$/m', '$1', $text );
		$text = (string) preg_replace( '/\s+\|\s+/', '; ', $text );
		// Every line ends like a sentence, so headings and table rows do not
		// run into the next line.
		$text = (string) preg_replace( '/(?<=[^\s.:?!;])[ \t]*$/m', '.', $text );
		$text = str_replace( [ '**', '`' ], '', $text );
		$text = (string) preg_replace( '/^\s*(>|[-*]|\d+\.)\s+/m', '', $text );

		return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	}
}
