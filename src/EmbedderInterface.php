<?php
namespace App;
interface EmbedderInterface {
    public function embed(string $text): array;
}
