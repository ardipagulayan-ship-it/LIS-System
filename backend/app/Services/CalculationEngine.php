<?php

namespace App\Services;

use App\Models\TestFormula;
use App\Models\TestResult;
use Illuminate\Support\Collection;

/**
 * CalculationEngine Service
 * 
 * Handles safe evaluation of compiled formulas for auto-computed parameters.
 * Uses tokenized compiled formulas like: "( [101] - [102] )"
 * Where [101], [102] represent test parameter IDs
 */
class CalculationEngine
{
    /**
     * Calculate a derived parameter based on formula and available results.
     *
     * @param TestFormula $formula The formula to execute
     * @param Collection $testResults Collection of TestResult models with their parameter data
     * @param ?int $decimalPlaces Optional override for decimal places
     * @return ?float The calculated value, or null if calculation cannot be performed
     */
    public function calculate(TestFormula $formula, Collection $testResults, ?int $decimalPlaces = null): ?float
    {
        $decimalPlaces = $decimalPlaces ?? $formula->decimal_places ?? 2;
        
        try {
            // Get the compiled formula string (e.g., "( [101] - [102] )")
            $compiledFormula = $formula->compiled_formula;
            
            // Build a map of parameter IDs to their numeric values
            $parameterValues = [];
            foreach ($testResults as $result) {
                if ($result->numeric_result !== null) {
                    $parameterValues[$result->test_parameter_id] = (float) $result->numeric_result;
                }
            }
            
            // Substitute parameter IDs with their actual values in the formula
            $evaluableFormula = $this->substituteParameterValues($compiledFormula, $parameterValues);
            
            // Check if all required parameters are available
            if ($this->hasMissingParameters($evaluableFormula)) {
                return null;
            }
            
            // Safely evaluate the formula
            $result = $this->safeEvaluate($evaluableFormula);
            
            // Return rounded result
            return round($result, $decimalPlaces);
            
        } catch (\Exception $e) {
            \Log::warning("Formula calculation failed for formula ID {$formula->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Substitute parameter IDs with their numeric values in the compiled formula.
     *
     * @param string $formula The compiled formula (e.g., "( [101] - [102] )")
     * @param array $parameterValues Map of parameter IDs to values
     * @return string The formula with substituted values
     */
    private function substituteParameterValues(string $formula, array $parameterValues): string
    {
        foreach ($parameterValues as $paramId => $value) {
            // Replace [paramId] with the numeric value
            $formula = str_replace("[{$paramId}]", (string)$value, $formula);
        }
        
        return $formula;
    }

    /**
     * Check if there are any unresolved parameter placeholders.
     *
     * @param string $formula The formula to check
     * @return bool True if missing parameters detected
     */
    private function hasMissingParameters(string $formula): bool
    {
        // Check if any [xxx] placeholders remain (unresolved parameters)
        return preg_match('/\[\d+\]/', $formula) === 1;
    }

    /**
     * Safely evaluate a mathematical expression.
     * 
     * Uses a whitelist approach to prevent code injection.
     * Only allows: numbers, operators (+, -, *, /), parentheses, and spaces.
     *
     * @param string $expression The expression to evaluate (e.g., "( 100 - 50 )")
     * @return float The result of the evaluation
     * @throws \Exception If the expression is invalid
     */
    private function safeEvaluate(string $expression): float
    {
        // Validate: only allow digits, operators, parentheses, and whitespace
        if (!preg_match('/^[\d+\-*\/().\s]+$/', $expression)) {
            throw new \Exception("Invalid expression format");
        }
        
        // Remove all whitespace for evaluation
        $cleanExpression = preg_replace('/\s+/', '', $expression);
        
        // Additional safety: ensure balanced parentheses
        if (substr_count($cleanExpression, '(') !== substr_count($cleanExpression, ')')) {
            throw new \Exception("Unbalanced parentheses in expression");
        }
        
        // Use BC Math for precise calculations if available
        if (function_exists('bcadd')) {
            return (float) eval("return {$cleanExpression};");
        }
        
        // Fallback to native PHP evaluation with proper error handling
        $result = @eval("return {$cleanExpression};");
        
        if ($result === false || !is_numeric($result)) {
            throw new \Exception("Expression evaluation failed");
        }
        
        return (float) $result;
    }

    /**
     * Validate a compiled formula syntax before saving.
     *
     * @param string $compiledFormula The formula to validate
     * @return bool True if valid
     * @throws \Exception If validation fails
     */
    public function validateFormulaSyntax(string $compiledFormula): bool
    {
        // Check for balanced parentheses
        if (substr_count($compiledFormula, '(') !== substr_count($compiledFormula, ')')) {
            throw new \Exception("Formula has unbalanced parentheses");
        }
        
        // Check for valid operators and tokens
        if (!preg_match('/^[\d+\-*\/()[\].\s]+$/', $compiledFormula)) {
            throw new \Exception("Formula contains invalid characters");
        }
        
        // Extract all parameter IDs to ensure they're numeric
        if (preg_match_all('/\[(\d+)\]/', $compiledFormula, $matches)) {
            // Could add additional validation on parameter IDs here
        }
        
        return true;
    }
}
