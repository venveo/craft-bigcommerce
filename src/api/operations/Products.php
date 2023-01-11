<?php

namespace venveo\bigcommerce\api\operations;

use JetBrains\PhpStorm\ArrayShape;
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
        $query = /** @lang graphql */
            <<<'EOD'
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
        description
      }
      minPurchaseQuantity
      maxPurchaseQuantity
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
      inventory {
        hasVariantInventory
        isInStock
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



    public static function getProductAvailabilityByOptions(int $productId, #[ArrayShape([[
        'optionEntityId' => 'int',
        'valueEntityId' => 'int'
    ]])] array $options = []) {
        $query = <<<'EOD'
query ProductsWithOptionSelections(
  $productId: Int!
  $optionValueIds: [OptionValueId!]
) # Use GraphQL Query Variables to inject your product ID
# and Option Value IDs
{
  site {
    product(
      entityId: $productId
      optionValueIds: $optionValueIds
    ) {
      ...ProductFields
    }
  }
}

fragment ProductFields on Product {
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
  name
  defaultImage {
    url(width: 1000)
  }
  sku
  minPurchaseQuantity
  maxPurchaseQuantity
  inventory {
    hasVariantInventory
    isInStock
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
  availabilityV2 {
    status
    description
  }
}

fragment MoneyFields on Money {
  value
  currencyCode
}
EOD;
        $response = ApiHelper::sendGraphQLRequest($query, [
            'productId' => $productId,
            'optionValueIds' => $options
        ], true);
        return $response;
    }

}