<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Debug;

use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

/**
 * @see \CodeIgniter\Debug\ExceptionHandlerTest
 */
final class ExceptionHandler extends BaseExceptionHandler implements ExceptionHandlerInterface
{

    /**
     * ResponseTrait needs this.
     */
    private  $request = null;

    /**
     * ResponseTrait needs this.
     */
    private $response = null;

    /**
     * Determines the correct way to display the error.
     */
    public function handle(
         $exception,
         $request,
         $response,
        int $statusCode,
        int $exitCode,
    ): void {
        // ResponseTrait needs these properties.

        // Determine possible directories of error views
        $addPath = (is_cli() ? 'cli' : 'html') . DIRECTORY_SEPARATOR;
        $path    = $this->viewPath . $addPath;
        // Determine the views
        $view    = $this->determineView($exception, $path, $statusCode);

        // Check if the view exists
        $viewFile = null;
        if (is_file($path . $view)) {
            $viewFile = $path . $view;
        }

        // Displays the HTML or CLI error code.
        $this->render($exception, $statusCode, $viewFile);

        exit($exitCode);
    }

    /**
     * Determines the view to display based on the exception thrown, HTTP status
     * code, whether an HTTP or CLI request, etc.
     *
     * @return string The filename of the view file to use
     */
    protected function determineView(
        Throwable $exception,
        string $templatePath,
        int $statusCode = 500,
    ): string {
        // Production environments should have a custom exception file.
        $view = 'production.php';

        if ($this->isDisplayErrorsEnabled()) {
            // If display_errors is enabled, shows the error details.
            $view = 'error_exception.php';
        }

        // 404 Errors
        if ($exception instanceof PageNotFoundException) {
            return 'error_404.php';
        }

        $templatePath = rtrim($templatePath, '\\/ ') . DIRECTORY_SEPARATOR;

        // Allow for custom views based upon the status code
        if (is_file($templatePath . 'error_' . $statusCode . '.php')) {
            return 'error_' . $statusCode . '.php';
        }

        return $view;
    }

    private function isDisplayErrorsEnabled(): bool
    {
        return in_array(
            strtolower(ini_get('display_errors')),
            ['1', 'true', 'on', 'yes'],
            true,
        );
    }
}
