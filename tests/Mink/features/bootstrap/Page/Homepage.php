<?php

use SensioLabs\Behat\PageObjectExtension\PageObject\Page, Behat\Mink\Exception\ResponseTextException,
    Behat\Behat\Context\Step;

class Homepage extends Page
{
    /**
     * @var string $path
     */
    protected $path = '/';

    /**
     * Searches the given term in the shop
     * @param string $searchTerm
     */
    public function searchFor($searchTerm)
    {
        $this->fillField('searchfield', $searchTerm);
        $this->pressButton('submit_search_btn');
        $this->verifyResponse();
    }

    public function receiveSearchResultsFor($searchTerm)
    {
        $this->fillField('searchfield', $searchTerm);
        $this->getSession()->wait(5000, "$('ul.searchresult').children().length > 0");
    }


    /**
     * Checks an emotion banner element
     * @param string $image
     * @param mixed $links
     * @throws Behat\Mink\Exception\ResponseTextException
     */
    public function checkBanner($image, $links = null)
    {
        $banners = $this->findAllEmotionParentElements('banner');

        $return = array();

        foreach ($banners as $banner) {

            $elements = array();

            $cssClass = 'div.' . str_replace(' ', '.', $banner->getAttribute('class'));

            $class = sprintf('div.emotion-element %s ', $cssClass);
            $elements['img'] = $this->find('css', $class . 'div.mapping img');

            if (isset($links)) {
                if (is_array($links)) {
                    $elements['mapping'] = array();
                    $mapping = array();

                    $maps = $this->findAllEmotionElements($cssClass, 'div.banner-mapping a');

                    foreach ($maps as $mapKey => $map) {
                        $class = sprintf(
                            'div.emotion-element %s div.banner-mapping a:nth-of-type(%d) ',
                            $cssClass,
                            $mapKey + 1
                        );

                        $mapping['a'] = $this->find('css', $class);

                        $elements['mapping'][] = $mapping;
                    }
                } else {
                    $elements['a'] = $this->find('css', $class . 'div.mapping a');
                }
            }

            $return[] = $elements;
        }

        foreach ($return as $itemKey => $item) {
            $check = array(
                array($item['img']->getAttribute('src'), $image)
            );

            if (isset($links)) {
                if (is_array($links)) {
                    foreach ($item['mapping'] as $subKey => $subitem) {
                        $check[] = array($subitem['a']->getAttribute('href'), $links[$subKey]['mapping']);
                    }
                } else {
                    $check[] = array($item['a']->getAttribute('href'), $links);
                }
            }

            $result = $this->getPage('Helper')->checkArray($check);
            if ($result === true) {
                unset($return[$itemKey]);
                return;
            }
        }

        $message = sprintf('The given banner was not found!');
        throw new ResponseTextException($message, $this->getSession());
    }

    /**
     * Checks an emotion blog element
     * @param array $articles
     * @throws Behat\Mink\Exception\ResponseTextException
     */
    public function checkBlogArticles($articles)
    {
        $return = array();
        $blogs = $this->findAllEmotionParentElements('blog');

        foreach ($blogs as $blogKey => $blog) {
            $cssClass = 'div.' . str_replace(' ', '.', $blog->getAttribute('class'));

            $entries = $this->findAllEmotionElements($cssClass, 'div.blog-entry');

            foreach ($entries as $entryKey => $entry) {
                $elements = array();

                $class = sprintf('div.emotion-element %s div.blog-entry:nth-of-type(%d) ', $cssClass, $entryKey + 1);
                $elements['a-image'] = $this->find('css', $class . 'div.blog_img a');
                $elements['a-title'] = $this->find('css', $class . 'h2 a');
                $elements['p-text'] = $this->find('css', $class . 'p');

                $return[$blogKey][] = $elements;
            }
        }

        foreach ($articles as $article) {
            $found = false;

            foreach ($blogs as $blogKey => $blog) {
                foreach ($return[$blogKey] as $itemKey => $item) {
                    $check = array(
                        array($item['a-image']->getAttribute('title'), $article['title']),
                        array($item['a-image']->getAttribute('style'), $article['image']),
                        array($item['a-image']->getAttribute('href'), $article['link']),
                        array($item['a-title']->getAttribute('title'), $article['title']),
                        array($item['a-title']->getAttribute('href'), $article['link']),
                        array($item['a-title']->getText(), $article['title']),
                        array($item['p-text']->getText(), $article['text'])
                    );

                    $result = $this->getPage('Helper')->checkArray($check);
                    if ($result === true) {
                        $found = true;
                        unset($return[$blogKey][$itemKey]);
                        break;
                    }
                }

                if ($found) {
                    break;
                }

                if ($blog == end($blogs)) {
                    $message = sprintf(
                        'The blog article "%s" with its given properties was not found!',
                        $article['title']
                    );
                    throw new ResponseTextException($message, $this->getSession());
                }
            }
        }
    }

    /**
     * Checks an emotion Youtube element
     * @param string $code
     * @throws Behat\Mink\Exception\ResponseTextException
     */
    public function checkYoutubeVideo($code)
    {
        $videos = $this->findAllEmotionParentElements('youtube');

        foreach ($videos as $video) {
            $cssClass = 'div.' . str_replace(' ', '.', $video->getAttribute('class'));

            $class = sprintf('div.emotion-element %s ', $cssClass);
            $source = $this->find('css', $class . 'iframe')->getAttribute('src');

            if (strpos($source, $code) !== false) {
                return;
            }
        }

        $message = sprintf('The YouTube-Video "%s" was not found!', $code);
        throw new ResponseTextException($message, $this->getSession());
    }

    /**
     * Checks an emotion slider element
     * @param string $type
     * @param array $slides
     * @throws Behat\Mink\Exception\ResponseTextException
     */
    public function checkSlider($type, $slides)
    {
        $sliders = $this->findAllEmotionSlider($type);

        $check = array();

        foreach ($slides as $slide) {
            $found = false;

            foreach ($sliders as $sliderKey => $slider) {
                foreach ($sliders[$sliderKey] as $itemKey => $item) {
                    switch ($type) {
                        case 'banner':
                            $check = array(
                                array($item['img']->getAttribute('src'), $slide['image'])
                            );
                            if (!empty($slide['title'])) {
                                $check[] = array($item['img']->getAttribute('title'), $slide['title']);
                            }
                            if (!empty($slide['alt'])) {
                                $check[] = array($item['img']->getAttribute('alt'), $slide['alt']);
                            }
                            if (!empty($slide['link'])) {
                                $check[] = array($item['a']->getAttribute('href'), $slide['link']);
                            }
                            break;

                        case 'manufacturer':
                            $check = array(
                                array($item['a-image']->getAttribute('href'), $slide['link']),
                                array($item['a-image']->getAttribute('title'), $slide['name']),
                                array($item['img']->getAttribute('src'), $slide['image']),
                                array($item['img']->getAttribute('alt'), $slide['name'])
                            );
                            break;

                        case 'article':
                            $check = array(
                                array($item['a-thumb']->getAttribute('href'), $slide['link']),
                                array($item['a-thumb']->getAttribute('title'), $slide['name']),
                                array($item['img']->getAttribute('src'), $slide['image']),
                                array($item['img']->getAttribute('title'), $slide['name']),
                                array($item['a-title']->getAttribute('href'), $slide['link']),
                                array($item['a-title']->getAttribute('title'), $slide['name']),
                                array($item['a-title']->getText(), $slide['name']),
                                $this->getPage('Helper')->toFloat(array($item['p-price']->getText(), $slide['price']))
                            );
                            break;
                    }

                    $result = $this->getPage('Helper')->checkArray($check);
                    if ($result === true) {
                        $found = true;
                        unset($sliders[$sliderKey][$itemKey]);
                        break;
                    }
                }

                if ($found) {
                    break;
                }

                if ($slider == end($sliders)) {
                    if ($type = 'banner') {
                        $message = sprintf('The image %s was not found in a slider', $slide['image']);
                    } else {
                        $message = sprintf('The slide "%s" with its given properties was not!', $slide['name']);
                    }
                    throw new ResponseTextException($message, $this->getSession());
                }
            }
        }
    }

    /**
     * Checks an emotion category teaser element
     * @param string $title
     * @param string $image
     * @param string $link
     * @throws Behat\Mink\Exception\ResponseTextException
     */
    public function checkCategoryTeaser($title, $image, $link)
    {
        $teasers = $this->findAllEmotionParentElements('category-teaser');

        foreach ($teasers as $teaser) {
            $cssClass = 'div.' . str_replace(' ', '.', $teaser->getAttribute('class'));

            $elements = array();

            $class = sprintf('div.emotion-element %s div.teaser_box ', $cssClass);
            $elements['a'] = $this->find('css', $class . 'a');
            $elements['div-image'] = $this->find('css', $class . 'div.teaser_img');
            $elements['h3'] = $this->find('css', $class . 'h3');

            $check = array(
                array($elements['a']->getAttribute('href'), $link),
                array($elements['a']->getAttribute('title'), $title),
                array($elements['div-image']->getAttribute('style'), $image),
                array($elements['h3']->getText(), $title)
            );

            $result = $this->getPage('Helper')->checkArray($check);
            if ($result === true) {
                break;
            }

            if ($teaser == end($teasers)) {
                $message = sprintf('The category teaser "%s" with its given properties was not found!', $title);
                throw new ResponseTextException($message, $this->getSession());
            }
        }
    }

    /**
     * Checks an emotion article element
     * @param array $data
     * @throws Behat\Mink\Exception\ResponseTextException
     */
    public function checkArticle($data)
    {
        $articles = $this->findAllEmotionParentElements('article');

        $title = '';

        foreach ($articles as $article) {
            $cssClass = 'div.' . str_replace(' ', '.', $article->getAttribute('class'));

            $elements = array();

            $class = sprintf('div.emotion-element %s div.artbox ', $cssClass);
            $elements['a-thumb'] = $this->find('css', $class . 'a.artbox_thumb');
            $elements['a-title'] = $this->find('css', $class . 'a.title');
            $elements['p-text'] = $this->find('css', $class . 'p.desc');
            $elements['p-price'] = $this->find('css', $class . 'p.price');
            $elements['a-more'] = $this->find('css', $class . 'a.more');

            $check = array();

            foreach ($data as $row) {
                switch ($row['property']) {
                    case 'image':
                        $check[] = array($elements['a-thumb']->getAttribute('style'), $row['value']);
                        break;

                    case 'title':
                        $check[] = array($elements['a-thumb']->getAttribute('title'), $row['value']);
                        $check[] = array($elements['a-title']->getAttribute('title'), $row['value']);
                        $check[] = array($elements['a-title']->getText(), $row['value']);
                        $check[] = array($elements['a-more']->getAttribute('title'), $row['value']);
                        $title = $row['value'];
                        break;

                    case 'text':
                        $check[] = array($elements['p-text']->getText(), $row['value']);
                        break;

                    case 'price':
                        $check[] = $this->getPage('Helper')->toFloat(array($elements['p-price']->getText(), $row['value']));
                        break;

                    case 'link':
                        $check[] = array($elements['a-thumb']->getAttribute('href'), $row['value']);
                        $check[] = array($elements['a-title']->getAttribute('href'), $row['value']);
                        $check[] = array($elements['a-more']->getAttribute('href'), $row['value']);
                        break;
                }
            }

            $result = $this->getPage('Helper')->checkArray($check);
            if ($result === true) {
                break;
            }

            if ($article == end($articles)) {
                $message = sprintf('The article "%s" with its given properties was not found!', $title);
                throw new ResponseTextException($message, $this->getSession());
            }
        }
    }

    /**
     * Helper function to find all emotion slider and their important tags
     * @param string $type
     * @return array
     */
    private function findAllEmotionSlider($type)
    {
        $selector = $type . '-slider';

        $sliders = $this->findAllEmotionParentElements($selector);

        $return = array();

        foreach ($sliders as $sliderKey => $slider) {
            $cssClass = 'div.' . str_replace(' ', '.', $slider->getAttribute('class'));

            switch ($type) {
                case 'banner':
                    $return[$sliderKey] = $this->findAllEmotionBannerSliderElements($cssClass);
                    break;

                case 'manufacturer':
                    $return[$sliderKey] = $this->findAllEmotionManufacturerSliderElements($cssClass);
                    break;

                case 'article':
                    $return[$sliderKey] = $this->findAllEmotionArticleSliderElements($cssClass);
                    break;
            }
        }

        return $return;
    }

    /**
     * Helper function to get all important tags of a banner slider
     * @param string $cssClass
     * @return array
     */
    private function findAllEmotionBannerSliderElements($cssClass)
    {
        $return = array();

        $class = 'div.slide';
        $slides = $this->findAllEmotionElements($cssClass, $class);

        foreach ($slides as $slideKey => $slide) {
            $elements = array();

            $class = sprintf('div.emotion-element %s div.slide:nth-of-type(%d) ', $cssClass, $slideKey + 1);
            $elements['a'] = $this->find('css', $class . 'a');
            $elements['img'] = $this->find('css', $class . 'img');

            $return[] = $elements;
        }
        return $return;
    }

    /**
     * Helper function to get all important tags of a manufacturer slider
     * @param string $cssClass
     * @return array
     */
    private function findAllEmotionManufacturerSliderElements($cssClass)
    {
        $return = array();

        $class = 'div.slide';
        $slides = $this->findAllEmotionElements($cssClass, $class);

        foreach ($slides as $slideKey => $slide) {
            $class = sprintf('div.slide:nth-of-type(%d) div.supplier', $slideKey + 1);
            $suppliers = $this->findAllEmotionElements($cssClass, $class);

            foreach ($suppliers as $supplierKey => $supplier) {
                $elements = array();

                $class = sprintf(
                    'div.emotion-element %s div.slide:nth-of-type(%d) div.supplier:nth-of-type(%d) ',
                    $cssClass,
                    $slideKey + 1,
                    $supplierKey + 1
                );
                $elements['a-image'] = $this->find('css', $class . 'a.image-wrapper');
                $elements['img'] = $this->find('css', $class . 'img');

                $return[] = $elements;
            }
        }
        return $return;
    }

    /**
     * Helper function to get all important tags of an article slider
     * @param string $cssClass
     * @return array
     */
    private function findAllEmotionArticleSliderElements($cssClass)
    {
        $return = array();

        $class = 'div.slide';
        $slides = $this->findAllEmotionElements($cssClass, $class);

        foreach ($slides as $slideKey => $slide) {
            $class = sprintf('div.slide:nth-of-type(%d) div.outer-article-box', $slideKey + 1);
            $articles = $this->findAllEmotionElements($cssClass, $class);

            foreach ($articles as $articleKey => $article) {
                $elements = array();

                $class = sprintf(
                    'div.emotion-element %s div.slide:nth-of-type(%d) div.outer-article-box:nth-of-type(%d) ',
                    $cssClass,
                    $slideKey + 1,
                    $articleKey + 1
                );
                $elements['a-thumb'] = $this->find('css', $class . 'a.article-thumb-wrapper');
                $elements['img'] = $this->find('css', $class . 'img');
                $elements['a-title'] = $this->find('css', $class . 'a.title');
                $elements['p-price'] = $this->find('css', $class . 'p.price');

                $return[] = $elements;
            }
        }
        return $return;
    }

    /**
     * Helper function to find all emotion parents elements of one type
     * @param string $type
     * @return array
     */

    private function findAllEmotionParentElements($type)
    {
        $selector = 'div.' . $type . '-element';

        $elements = $this->findAllEmotionElements($selector);

        return $elements;
    }

    /**
     * Helper function to find all emotion sub-elements of a parent
     * @param string $parentClass
     * @param string $class
     * @return array
     */
    private function findAllEmotionElements($parentClass, $class = '')
    {
        $selector = 'div.emotion-element ' . $parentClass;

        if (!empty($class)) {
            $selector .= ' ' . $class;
        }

        $elements = $this->findAll('css', $selector);

        return $elements;
    }

    /**
     * Compares the comparison list with the given list of articles
     * @param array $articles
     * @throws Behat\Mink\Exception\ResponseTextException
     */
    public function checkComparison($articles)
    {
        $message = 'There are %d articles in the comparison (should be %d)';
        $articlesInComparison = $this->getPage('Helper')->countElements(
            'div.compare_article',
            $message,
            count($articles)
        );

        foreach ($articles as $articleKey => $article) {
            foreach ($articlesInComparison as $articleInComparisonKey => $articleInComparison) {

                $locator = sprintf('div.compare_article:nth-of-type(%d) ', $articleInComparisonKey + 2);

                $elements = array(
                    'a-picture' => $this->find('css', $locator . 'div.picture a'),
                    'img' => $this->find('css', $locator . 'div.picture img'),
                    'h3-a-name' => $this->find('css', $locator . 'div.name h3 a'),
                    'a-name' => $this->find('css', $locator . 'div.name a.button-right'),
                    'div-votes' => $this->find('css', $locator . 'div.votes div.star'),
                    'p-desc' => $this->find('css', $locator . 'div.desc'),
                    'strong-price' => $this->find('css', $locator . 'div.price strong')
                );

                $check = array();

                if (!empty($article['image'])) {
                    $check[] = array($elements['img']->getAttribute('src'), $article['image']);
                }

                if (!empty($article['name'])) {
                    $check[] = array($elements['a-picture']->getAttribute('title'), $article['name']);
                    $check[] = array($elements['img']->getAttribute('alt'), $article['name']);
                    $check[] = array($elements['h3-a-name']->getAttribute('title'), $article['name']);
                    $check[] = array($elements['h3-a-name']->getText(), $article['name']);
                    $check[] = array($elements['a-name']->getAttribute('title'), $article['name']);
                }

                if (!empty($article['ranking'])) {
                    $check[] = array($elements['div-votes']->getAttribute('class'), $article['ranking']);
                }

                if (!empty($article['text'])) {
                    $check[] = array($elements['p-desc']->getText(), $article['text']);
                }

                if (!empty($article['price'])) {
                    $check[] = $this->getPage('Helper')->toFloat(array($elements['strong-price']->getText(), $article['price']));
                }

                if (!empty($article['link'])) {
                    $check[] = array($elements['a-picture']->getAttribute('href'), $article['link']);
                    $check[] = array($elements['h3-a-name']->getAttribute('href'), $article['link']);
                    $check[] = array($elements['a-name']->getAttribute('href'), $article['link']);
                }

                $result = $this->getPage('Helper')->checkArray($check);

                if ($result === true) {
                    unset($articlesInComparison[$articleInComparisonKey]);
                    break;
                }

                if ($articleInComparison == end($articlesInComparison)) {
                    $message = sprintf(
                        'The article on position %d was not found!',
                        $articleKey + 1
                    );
                    throw new ResponseTextException($message, $this->getSession());
                }
            }
        }
    }
}
