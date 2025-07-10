<?php
class BookModel {
    private $file = __DIR__ . '/../LIBRARY_DATA_FINAL_clean.csv';
    private $headers = [];
    private $books = [];

    public function __construct() {
        $this->loadData();
    }

    private function loadData() {
        if (($handle = fopen($this->file, "r")) !== FALSE) {
            $this->headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                $this->books[] = $data;
            }
            fclose($handle);
        }
    }

    public function getCategories() {
        $categories = [];
        foreach ($this->books as $book) {
            $cat = trim($book[8]);
            if ($cat && !in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }
        sort($categories);
        return $categories;
    }

    public function getBooks($search = '', $category = '') {
        $search = strtolower($search);
        $filtered = [];

        foreach ($this->books as $index => $book) {
            $title = strtolower($book[1]);
            $summary = strtolower($book[6]);
            $cat = $book[8];

            $matchesSearch = $search === '' || strpos($title, $search) !== false || strpos($summary, $search) !== false;
            $matchesCategory = $category === '' || $cat === $category;

            if ($matchesSearch && $matchesCategory) {
                $filtered[] = ['index' => $index + 1, 'data' => $book];
            }
        }
        return $filtered;
    }

    public function getBookById($id) {
        $id = intval($id) - 1;
        if (isset($this->books[$id])) {
            return $this->books[$id];
        }
        return null;
    }
}
