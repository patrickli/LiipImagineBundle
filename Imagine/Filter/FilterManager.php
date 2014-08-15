<?php

namespace Liip\ImagineBundle\Imagine\Filter;

use Imagine\Image\ImagineInterface;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FilterManager
{
    /**
     * @var FilterConfiguration
     */
    protected $filterConfig;

    /**
     * @var ImagineInterface
     */
    protected $imagine;

    /**
     * @var LoaderInterface[]
     */
    protected $loaders = array();

    /**
     * @param FilterConfiguration $filterConfig
     * @param ImagineInterface    $imagine
     */
    public function __construct(FilterConfiguration $filterConfig, ImagineInterface $imagine)
    {
        $this->filterConfig = $filterConfig;
        $this->imagine = $imagine;
    }

    /**
     * Adds a loader to handle the given filter.
     *
     * @param string $filter
     * @param LoaderInterface $loader
     *
     * @return void
     */
    public function addLoader($filter, LoaderInterface $loader)
    {
        $this->loaders[$filter] = $loader;
    }

    /**
     * @return FilterConfiguration
     */
    public function getFilterConfiguration()
    {
        return $this->filterConfig;
    }

    /**
     * @param BinaryInterface $binary
     * @param array $config
     *
     * @throws \InvalidArgumentException
     *
     * @return Binary
     */
    public function apply(BinaryInterface $binary, array $config)
    {
        $config = array_replace(
            array(
                'filters' => array(),
                'quality' => 100
            ),
            $config
        );

        $image = $this->imagine->load($binary->getContent());

        foreach ($config['filters'] as $eachFilter => $eachOptions) {
            if (!isset($this->loaders[$eachFilter])) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not find filter loader for "%s" filter type', $eachFilter
                ));
            }

            $image = $this->loaders[$eachFilter]->load($image, $eachOptions);
        }

        $options = array(
            'quality' => $config['quality']
        );
        if (isset($config['jpeg_quality'])) {
            $options['jpeg_quality'] = $config['jpeg_quality'];
        }
        if (isset($config['png_compression_level'])) {
            $options['png_compression_level'] = $config['png_compression_level'];
        }
        if (isset($config['png_compression_filter'])) {
            $options['png_compression_filter'] = $config['png_compression_filter'];
        }
        $filteredContent = $image->get($binary->getFormat(), $options);

        return new Binary($filteredContent, $binary->getMimeType(), $binary->getFormat());
    }

    /**
     * Apply the provided filter set on the given binary.
     *
     * @param BinaryInterface $binary
     * @param string          $filter
     * @param array           $runtimeConfig
     *
     * @throws \InvalidArgumentException
     *
     * @return BinaryInterface
     */
    public function applyFilter(BinaryInterface $binary, $filter, array $runtimeConfig = array())
    {
        $config = array_replace_recursive(
            $this->getFilterConfiguration()->get($filter),
            $runtimeConfig
        );

        return $this->apply($binary, $config);
    }
}
