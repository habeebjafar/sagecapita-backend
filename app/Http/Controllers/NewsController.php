<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\News;

class NewsController extends Controller
{
    /**
     * Instantiate a new NewsController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Create a new news.
     *
     * @param Request $request
     * @return Response
     */
    public function createNews(Request $request)
    {
        try {

            self::_createNewsValidation($request);

            try {
                $news = self::_assembleCreateNews($request);
                $news->save();

                return response()->json(['news' => $news, 'message' => 'CREATED'], 201);
            } catch (\Exception $e) {
                return response()->json(['message' => 'News Creation Failed!'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => ['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the news data']], 400);
        }
    }

    /**
     * Get all News.
     *
     * @param  Request $request
     * @return Response
     */
    public function getNewss(Request $request)
    {
        $perPage = $request->input('per_page') ?? 8;
        $nameContains = $request->input('name');
        $year = $request->input('year');

        $newss = News::orderBy('article_date', 'DESC');

        if ($nameContains) {
            $newss->whereRaw(
                "MATCH(title) AGAINST(? IN BOOLEAN MODE)",
                [$nameContains . '*']
            );
        }

        if ($year) {
            $newss->whereRaw(
                "YEAR(article_date) = ?",
                [$year]
            );
        }

        return response()->json(['newss' =>  $newss->paginate($perPage)], 200);
    }

    /**
     * Get one news.
     *
     * @return Response
     */
    public function getNews($id)
    {
        try {
            $news = News::findOrFail($id);

            return response()->json(['news' => $news], 200);
        } catch (\Exception $e) {

            return response()->json(['news' => 'news not found!'], 404);
        }
    }

    /**
     * Get total news.
     *
     * @return Response
     */
    public function getTotalNews()
    {
        $news
            = News::select(\DB::raw('count(id) AS count'))
            ->first();

        if ($news->count) {
            return response()->json(['news_count' => $news->count], 200);
        } else {
            return response()->json(['message' => 'No news found!'], 404);
        }
    }

    /**
     * Get news years.
     *
     * @return Response
     */
    public function getNewsYears()
    {
        $news
            = News::select(\DB::raw('DISTINCT year(article_date) AS year'))
            ->orderBy('article_date', 'DESC')
            ->get();

        if ($news) {
            $newsGrouped = $news->mapToGroups(
                function ($item, $key) {
                    return ['years' => $item['year']];
                }
            );

            return response()->json(['news_years' => $newsGrouped], 200);
        } else {
            return response()->json(['message' => 'No news years found!'], 404);
        }
    }

    /**
     * Create a new news.
     *
     * @param Request $request
     * @return Response
     */
    public function updateNews(Request $request, $newsId)
    {
        try {

            self::_updateNewsValidation($request);

            try {
                $news = News::findOrFail($newsId);

                try {
                    $news = self::_assembleUpdateNews($request, $news);
                    $news->save();

                    return response()->json(['news' => $news, 'message' => 'UPDATED'], 200);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'News Update Failed!'], 409);
                }
            } catch (\Exception $e) {

                return response()->json(['message' => 'news not found!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => ['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the news data']], 400);
        }
    }

    /**
     * Delete one news.
     *
     * @return Response
     */
    public function deleteNews($newsId)
    {
        try {
            $news = News::findOrFail($newsId);

            try {
                $news->delete();

                return response()->json(['message' => 'news deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'news deletion failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'news not found!'], 404);
        }
    }

    /**
     * Assemble one news.
     * 
     * @param Request $request
     * 
     * @param News $news
     * 
     * @return News $news
     */
    private function _assembleCreateNews(Request $request, News $news = null)
    {
        $news || ($news = new News);
        $news->title = $request->input('title');
        $news->source = $request->input('source');
        $news->photo_link = $request->input('photo_link');
        $news->article_link = $request->input('article_link');
        $news->article_date = $request->input('article_date');

        return $news;
    }

    /**
     * Assemble one news.
     * 
     * @param Request $request
     * 
     * @param News $news
     * 
     * @return News $news
     */
    private function _assembleUpdateNews(Request $request, News $news = null)
    {
        $news || ($news = new News);
        $news->title = $request->input('title');
        $news->source = $request->input('source');
        $news->photo_link = $request->input('photo_link');
        $news->article_link = $request->input('article_link');
        $news->article_date = $request->input('article_date');

        return $news;
    }

    /**
     * Get one news.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _createNewsValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'source' => 'required|string|max:60',
                'title' => 'required|string|max:255',
                'photo_link' => 'required|url',
                'article_link' => 'required|url',
                'article_date' => 'required|date'
            ]
        );
    }

    /**
     * Get one news.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _updateNewsValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'source' => 'required|string|max:60',
                'title' => 'required|string|max:255',
                'photo_link' => 'required|url',
                'article_link' => 'required|url',
                'article_date' => 'required|date'
            ]
        );
    }

}
