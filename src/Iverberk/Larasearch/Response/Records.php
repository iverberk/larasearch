<?php namespace Iverberk\Larasearch\Response;

use Illuminate\Support\Collection;
use Iverberk\Larasearch\Response;

class Records extends Collection {

    /**
     * Contains an Elasticsearch response wrapper
     *
     * @var \Iverberk\Larasearch\Response
     */
    private $response;

    /**
     * Construct a collection of Eloquent models based on the search result
     *
     * @access public
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;

        $ids = array_map(function ($hit)
        {
            return $hit['_id'];
        }, $this->response->getHits());

        $model = $response->getModel();

        parent::__construct($model::whereIn('id', $ids)->get()->toArray());
    }

}
