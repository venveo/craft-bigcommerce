<?php

namespace venveo\bigcommerce\api\operations;

use venveo\bigcommerce\base\ApiOperationInterface;
use venveo\bigcommerce\helpers\ApiHelper;

class Products implements ApiOperationInterface
{
    /**
     * Gets product information from BigCommerce. Note, this will return any customer-specific pricing if the customer is logged in
     * @param $productId
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function getProductInformationById($productId): \Psr\Http\Message\ResponseInterface
    {
        $query = <<<'EOD'
query productById(
  $productId: Int!
)
{
  site {
    product(entityId: $productId) {
      id
      entityId
      name
      sku
      availabilityV2 {
        status
      }
      variants(first: 25) {
        edges {
          node {
            prices {
              price {
                ...MoneyFields
              }
              priceRange {
                min {
                  ...MoneyFields
                }
                max {
                  ...MoneyFields
                }
              }
              salePrice {
                ...MoneyFields
              }
              retailPrice {
                ...MoneyFields
              }
            }
            sku
            id
            entityId
          }
        }
      }
      productOptions(first: 25) {
        edges {
          node {
            entityId
            displayName
            isRequired
            __typename
            ... on CheckboxOption {
              checkedByDefault
            }
            ... on MultipleChoiceOption {
              values(first: 10) {
                edges {
                  node {
                    entityId
                    label
                    isDefault
                    ... on SwatchOptionValue {
                      hexColors
                      imageUrl(width: 200)
                    }
                  }
                }
              }
            }
          }
        }
      }
      prices {
        price {
          ...MoneyFields
        }
        priceRange {
          min {
            ...MoneyFields
          }
          max {
            ...MoneyFields
          }
        }
        salePrice {
          ...MoneyFields
        }
        retailPrice {
          ...MoneyFields
        }
        saved {
          ...MoneyFields
        }
        bulkPricing {
          minimumQuantity
          maximumQuantity
          ... on BulkPricingFixedPriceDiscount {
            price
          }
          ... on BulkPricingPercentageDiscount {
            percentOff
          }
          ... on BulkPricingRelativePriceDiscount {
            priceAdjustment
          }
        }
      }
      brand {
        name
      }
    }
  }
}

fragment MoneyFields on Money {
  value
  currencyCode
}
EOD;
        $response = ApiHelper::sendGraphQLRequest($query, [
            'productId' => $productId
        ], true);
        return $response;
    }

}