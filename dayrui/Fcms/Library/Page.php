<?php namespace Phpcmf\Library;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/



class Page {

	/**
	 * Base URL
	 *
	 * The page that we're linking to
	 *
	 * @var	string
	 */
	protected $base_url		= '';

	/**
	 * Prefix
	 *
	 * @var	string
	 */
	protected $prefix = '';

	/**
	 * Suffix
	 *
	 * @var	string
	 */
	protected $suffix = '';

	/**
	 * Total number of items
	 *
	 * @var	int
	 */
	protected $total_rows = 0;

	/**
	 * Number of links to show
	 *
	 * Relates to "digit" type links shown before/after
	 * the currently viewed page.
	 *
	 * @var	int
	 */
	protected $num_links = 2;

	/**
	 * Items per page
	 *
	 * @var	int
	 */
	public $per_page = 10;

	/**
	 * Current page
	 *
	 * @var	int
	 */
	public $cur_page = 0;

	/**
	 * Use page numbers flag
	 *
	 * Whether to use actual page numbers instead of an offset
	 *
	 * @var	bool
	 */
	protected $use_page_numbers = true;

	/**
	 * First link
	 *
	 * @var	string
	 */
	protected $first_link = '&lsaquo; First';

	/**
	 * Next link
	 *
	 * @var	string
	 */
	protected $next_link = '&gt;';

	/**
	 * Previous link
	 *
	 * @var	string
	 */
	protected $prev_link = '&lt;';

	/**
	 * Last link
	 *
	 * @var	string
	 */
	protected $last_link = 'Last &rsaquo;';

	/**
	 * URI Segment
	 *
	 * @var	int
	 */
	protected $uri_segment = 0;

	/**
	 * Full tag open
	 *
	 * @var	string
	 */
	protected $full_tag_open = '';

	/**
	 * Full tag close
	 *
	 * @var	string
	 */
	protected $full_tag_close = '';

	/**
	 * First tag open
	 *
	 * @var	string
	 */
	protected $first_tag_open = '';

	/**
	 * First tag close
	 *
	 * @var	string
	 */
	protected $first_tag_close = '';
	/**
	 * First tag open
	 *
	 * @var	string
	 */
	protected $total_tag_open = '';

	/**
	 * First tag close
	 *
	 * @var	string
	 */
	protected $total_tag_close = '';
	protected $total_link = '';

	/**
	 * Last tag open
	 *
	 * @var	string
	 */
	protected $last_tag_open = '';

	/**
	 * Last tag close
	 *
	 * @var	string
	 */
	protected $last_tag_close = '';

	/**
	 * First URL
	 *
	 * An alternative URL for the first page
	 *
	 * @var	string
	 */
	protected $first_url = '';

	/**
	 * Current tag open
	 *
	 * @var	string
	 */
	protected $cur_tag_open = '<strong>';

	/**
	 * Current tag close
	 *
	 * @var	string
	 */
	protected $cur_tag_close = '</strong>';

	/**
	 * Next tag open
	 *
	 * @var	string
	 */
	protected $next_tag_open = '';

	/**
	 * Next tag close
	 *
	 * @var	string
	 */
	protected $next_tag_close = '';

	/**
	 * Previous tag open
	 *
	 * @var	string
	 */
	protected $prev_tag_open = '';

	/**
	 * Previous tag close
	 *
	 * @var	string
	 */
	protected $prev_tag_close = '';

	/**
	 * Number tag open
	 *
	 * @var	string
	 */
	protected $num_tag_open = '';

	/**
	 * Number tag close
	 *
	 * @var	string
	 */
	protected $num_tag_close = '';

	/**
	 * Page query string flag
	 *
	 * @var	bool
	 */
	protected $page_query_string = FALSE;

	/**
	 * Query string segment
	 *
	 * @var	string
	 */
	protected $query_string_segment = 'per_page';

	/**
	 * Display pages flag
	 *
	 * @var	bool
	 */
	protected $display_pages = TRUE;

	/**
	 * Attributes
	 *
	 * @var	string
	 */
	protected $_attributes = '';

	/**
	 * Link types
	 *
	 * "rel" attribute
	 *
	 * @see	CI_Pagination::_attr_rel()
	 * @var	array
	 */
	protected $_link_types = array();

	/**
	 * Reuse query string flag
	 *
	 * @var	bool
	 */
	protected $reuse_query_string = FALSE;

	/**
	 * Use global URL suffix flag
	 *
	 * @var	bool
	 */
	protected $use_global_url_suffix = FALSE;

	/**
	 * Data page attribute
	 *
	 * @var	string
	 */
	protected $data_page_attr = 'data-ci-pagination-page';


	// --------------------------------------------------------------------

	/**
	 * Initialize Preferences
	 *
	 * @param	array	$params	Initialization parameters
	 * @return	CI_Pagination
	 */
	public function initialize(array $params = array())
	{
		isset($params['attributes']) OR $params['attributes'] = array();
		if (is_array($params['attributes']))
		{
			$this->_parse_attributes($params['attributes']);
			unset($params['attributes']);
		}

		// Deprecated legacy support for the anchor_class option
		// Should be removed in CI 3.1+
		if (isset($params['anchor_class']))
		{
			empty($params['anchor_class']) OR $attributes['class'] = $params['anchor_class'];
			unset($params['anchor_class']);
		}

		foreach ($params as $key => $val)
		{
			if (property_exists($this, $key))
			{
				$this->$key = $val;
			}
		}

		return $this;
	}

	/**
	 * Parse attributes
	 *
	 * @param	array	$attributes
	 * @return	void
	 */
	protected function _parse_attributes($attributes)
	{
		isset($attributes['rel']) OR $attributes['rel'] = TRUE;
		$this->_link_types = ($attributes['rel'])
			? array('start' => 'start', 'prev' => 'prev', 'next' => 'next')
			: array();
		unset($attributes['rel']);

		$this->_attributes = '';
		foreach ($attributes as $key => $value)
		{
			$this->_attributes .= ' '.$key.'="'.$value.'"';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Add "rel" attribute
	 *
	 * @link	http://www.w3.org/TR/html5/links.html#linkTypes
	 * @param	string	$type
	 * @return	string
	 */
	protected function _attr_rel($type)
	{
		if (isset($this->_link_types[$type]))
		{
			unset($this->_link_types[$type]);
			return ' rel="'.$type.'"';
		}

		return '';
	}


	// --------------------------------------------------------------------

	/**
	 * Generate the pagination links
	 *
	 * @return	string
	 */
	public function create_links()
	{

        // If our item count or per-page total is zero there is no need to continue.
        if ($this->total_rows === 0 OR $this->per_page === 0)
        {
            return '';
        }

        // Calculate the total number of pages
        $num_pages = (int) ceil($this->total_rows / $this->per_page);

        // Is there only one page? Hm... nothing more to do here then.
        if ($num_pages === 1)
        {
            return '';
        }

        // Check the user defined number of links.
        $this->num_links = (int) $this->num_links;

		$get = [];

        // Put together our base and first URLs.
        $this->base_url = trim($this->base_url);

        // Determine the current page number.
        $base_page = ($this->use_page_numbers) ? 1 : 0;

        $this->cur_page = max(1, (int)$_GET['page']);

        // If something isn't quite right, back to the default base page.
        if ( $this->use_page_numbers && (int) $this->cur_page === 0)
        {
            $this->cur_page = $base_page;
        }
        else
        {
            // Make sure we're using integers for comparisons later.
            $this->cur_page = (int) $this->cur_page;
        }

        // Is the page number beyond the result range?
        // If so, we show the last page.
        if ($this->use_page_numbers)
        {
            if ($this->cur_page > $num_pages)
            {
                $this->cur_page = $num_pages;
            }
        }
        elseif ($this->cur_page > $this->total_rows)
        {
            $this->cur_page = ($num_pages - 1) * $this->per_page;
        }

        $uri_page_number = $this->cur_page;

        // If we're using offset instead of page numbers, convert it
        // to a page number, so we can generate the surrounding number links.
        if ( ! $this->use_page_numbers)
        {
            $this->cur_page = (int) floor(($this->cur_page/$this->per_page) + 1);
        }

        // Calculate the start and end numbers. These determine
        // which number to start and end the digit links with.
        $start	= (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
        $end	= (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;

        // And here we go...$total_rows
        $output = '';

		if ($this->total_link) {
			$output .= $this->total_tag_open.'<a>'.dr_lang($this->total_link, $this->total_rows).'</a>'.$this->total_tag_close;
		}

        // Render the "First" link.
        if ($this->first_link !== FALSE && $this->cur_page > ($this->num_links + 1))
        {
            // Take the general parameters, and squeeze this pagination-page attr in for JS frameworks.
            $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, 1);

            $output .= $this->first_tag_open.'<a href="'.$this->_get_link_url(1).'"'.$attributes.$this->_attr_rel('start').'>'
                .$this->first_link.'</a>'.$this->first_tag_close;
        }

        // Render the "Previous" link.
        if ($this->prev_link !== FALSE && $this->cur_page !== 1)
        {
            $i = ($this->use_page_numbers) ? $uri_page_number - 1 : $uri_page_number - $this->per_page;

            $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, (int) $i);

            if ($i === $base_page)
            {
                // First page
                $output .= $this->prev_tag_open.'<a href="'.$this->_get_link_url($i).'"'.$attributes.$this->_attr_rel('prev').'>'
                    .$this->prev_link.'</a>'.$this->prev_tag_close;
            }
            else
            {
                $output .= $this->prev_tag_open.'<a href="'.$this->_get_link_url($i).'"'.$attributes.$this->_attr_rel('prev').'>'
                    .$this->prev_link.'</a>'.$this->prev_tag_close;
            }

        }

        // Render the pages
        if ($this->display_pages !== FALSE)
        {
            // Write the digit links
            for ($loop = $start -1; $loop <= $end; $loop++)
            {
                $i = ($this->use_page_numbers) ? $loop : ($loop * $this->per_page) - $this->per_page;

                $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, (int) $i);

                if ($i >= $base_page)
                {
                    if ($this->cur_page === $loop)
                    {
                        // Current page
                        $output .= $this->cur_tag_open.$loop.$this->cur_tag_close;
                    }
                    elseif ($i === $base_page)
                    {
                        // First page
                        $output .= $this->num_tag_open.'<a href="'.$this->_get_link_url(1).'"'.$attributes.$this->_attr_rel('start').'>'
                            .$loop.'</a>'.$this->num_tag_close;
                    }
                    else
                    {
                        $output .= $this->num_tag_open.'<a href="'.$this->_get_link_url($i).'"'.$attributes.$this->_attr_rel('start').'>'
                            .$loop.'</a>'.$this->num_tag_close;
                    }
                }
            }
        }

        // Render the "next" link
        if ($this->next_link !== FALSE && $this->cur_page < $num_pages)
        {
            $i = ($this->use_page_numbers) ? $this->cur_page + 1 : $this->cur_page * $this->per_page;

            $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, (int) $i);

            $output .= $this->next_tag_open.'<a href="'.$this->_get_link_url($i).'"'.$attributes
                .$this->_attr_rel('next').'>'.$this->next_link.'</a>'.$this->next_tag_close;
        }

        // Render the "Last" link
        if ($this->last_link !== FALSE && ($this->cur_page + $this->num_links) < $num_pages)
        {
            $i = ($this->use_page_numbers) ? $num_pages : ($num_pages * $this->per_page) - $this->per_page;

            $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, (int) $i);

            $output .= $this->last_tag_open.'<a href="'.$this->_get_link_url($i).'"'.$attributes.'>'
                .$this->last_link.'</a>'.$this->last_tag_close;
        }

        // Kill double slashes. Note: Sometimes we can end up with a double slash
        // in the penultimate link so we'll kill all double slashes.
        $output = preg_replace('#([^:])//+#', '\\1/', $output);

        // Add the wrapper HTML if exists
        return $this->full_tag_open.$output.$this->full_tag_close;
    }

    private function _get_link_url($page) {

        return $page <=1 && $this->first_url ? $this->first_url : str_replace('{page}', $page, $this->base_url);
    }
}
