# Simple Q&A Accordion

Lekka wtyczka WordPress dodająca panel do zarządzania pytaniami i odpowiedziami oraz shortcode do wyświetlania ich w formie klasycznego akordeonu.

## Funkcje
- Własny typ wpisu **Q&A** w panelu administracyjnym (tytuł jako pytanie, pole edytora jako odpowiedź).
- Obsługa kolejności poprzez pole "Kolejność" (menu order).
- Shortcode `[simple_qa]` renderujący akordeon z ikonką "+" i rozwijaną odpowiedzią (po kliknięciu jedno pytanie rozwija się naraz).
- Prosta, klasyczna stylizacja i skrypt zbudowany na jQuery.

## Użycie
1. Skopiuj katalog `wp-qa-accordion` do katalogu `wp-content/plugins/` i aktywuj wtyczkę w kokpicie WordPressa.
2. W menu "Q&A" dodaj nowe pytania (tytuł) i odpowiedzi (edytor treści w metaboxie "Odpowiedź").
3. Umieść shortcode `[simple_qa]` w treści strony lub wpisu, aby wyświetlić akordeon.
4. Opcjonalnie ustaw kolejność wyświetlania, korzystając z pola "Kolejność" dla każdego pytania.

Możesz odwrócić kolejność wyświetlania dodając parametr `order="DESC"` do shortcode.
