<?php

namespace App\Services;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Authentication\Authenticate;
use Illuminate\Support\Facades\Log;

class Neo4JService 
{
    protected $client;

    public function __construct()
    {
        // Default to localhost:7687 (Bolt port) instead of 7474 (HTTP port)
        $uri = config('services.neo4j.url', 'bolt://localhost:7687');
        $username = config('services.neo4j.username', 'neo4j');
        $password = config('services.neo4j.password', 'password');

        try {
            $this->client = ClientBuilder::create()
                ->withDriver('bolt', $uri, Authenticate::basic($username, $password))
                ->build();
        } catch (\Exception $e) {
            Log::error('❌ Failed to connect to Neo4j via Bolt: ' . $e->getMessage());
            throw $e;
        }
    } 

    protected function runQuery(string $cypher, array $params = [])
    {
        try {
            $result = $this->client->run($cypher, $params);
            return $result->getResults();
        } catch (\Exception $e) {
            Log::error('❌ Neo4j Query Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function testConnection(): array
    {
        try {
            $result = $this->runQuery('RETURN 1 AS test');
            info($result);
            return [
                'status' => 'success',
                'message' => $result
            ];
        } catch (\Exception $e) {
            Log::error('❌ Neo4j Connection Test Failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * DIAGNOSTIC METHOD
     * Debug the EXPRESS questionnaire node and its connections
     * Updated to work with new node types and relationships
     */
    public function diagnoseExpressNodes(): array
    {
        try {
            // Check if EXPRESS questionnaire exists (can be Component or Page)
            $expressCheck = $this->runQuery("
                MATCH (c)
                WHERE (c:Component OR c:Page) 
                AND (c.name = 'EXPRESS' OR c.name = 'EXPRESS questionnaire' OR toLower(c.name) CONTAINS 'express questionnaire')
                RETURN c AS component
            ");
            
            if ($expressCheck->count() === 0) {
                return [
                    'status' => 'error',
                    'message' => 'EXPRESS questionnaire node not found in database',
                    'suggestion' => 'Create the EXPRESS questionnaire node first'
                ];
            }

            $expressNode = $expressCheck->first()->get('component');
            $expressElementId = $expressNode->getElementId();

            // Check connections from EXPRESS questionnaire
            $connections = $this->runQuery("
                MATCH (c)-[r]-(n)
                WHERE (c:Component OR c:Page) 
                AND (c.name = 'EXPRESS' OR c.name = 'EXPRESS questionnaire' OR toLower(c.name) CONTAINS 'express questionnaire')
                RETURN type(r) AS relationshipType, 
                       labels(n) AS nodeLabels, 
                       n.name AS nodeName,
                       n.embedding IS NOT NULL AS hasEmbedding,
                       size(n.embedding) AS embeddingSize
                ORDER BY relationshipType, nodeName
            ");

            $connectionData = [];
            foreach ($connections as $conn) {
                $connectionData[] = [
                    'relationship' => $conn->get('relationshipType'),
                    'nodeLabels' => $conn->get('nodeLabels'),
                    'nodeName' => $conn->get('nodeName'),
                    'hasEmbedding' => $conn->get('hasEmbedding'),
                    'embeddingSize' => $conn->get('embeddingSize')
                ];
            }

            // Check for all node types in the database
            $nodeTypeCounts = $this->runQuery("
                MATCH (n)
                WITH labels(n) AS nodeLabels
                UNWIND nodeLabels AS label
                RETURN label, count(*) AS count
                ORDER BY count DESC
            ");

            $nodeCounts = [];
            foreach ($nodeTypeCounts as $count) {
                $nodeCounts[] = [
                    'label' => $count->get('label'),
                    'count' => $count->get('count')
                ];
            }

            // Check relationship types
            $relationshipTypeCounts = $this->runQuery("
                MATCH ()-[r]->()
                RETURN type(r) AS relationshipType, count(*) AS count
                ORDER BY count DESC
            ");

            $relationshipCounts = [];
            foreach ($relationshipTypeCounts as $count) {
                $relationshipCounts[] = [
                    'type' => $count->get('relationshipType'),
                    'count' => $count->get('count')
                ];
            }

            // Check vector indexes
            $indexes = $this->runQuery("SHOW INDEXES");
            $vectorIndexes = [];
            foreach ($indexes as $index) {
                $indexData = $index->toArray();
                if (isset($indexData['type']) && $indexData['type'] === 'VECTOR') {
                    $vectorIndexes[] = $indexData;
                }
            }

            return [
                'status' => 'success',
                'expressNode' => [
                    'elementId' => $expressElementId,
                    'properties' => $expressNode->getProperties()->toArray(),
                    'labels' => $expressNode->getLabels()->toArray()
                ],
                'connections' => $connectionData,
                'nodeTypeCounts' => $nodeCounts,
                'relationshipTypeCounts' => $relationshipCounts,
                'vectorIndexes' => $vectorIndexes,
                'totalConnections' => count($connectionData)
            ];

        } catch (\Exception $e) {
            Log::error('❌ EXPRESS Node Diagnosis Failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getContext(array $embedding, string $currentPageName): array
    {
        $embeddingHash = md5(json_encode($embedding));
        $cacheKey = 'neo4j_context_' . $embeddingHash . '_' . md5($currentPageName);
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(2), function() use ($embedding, $currentPageName) {
        $startNodeId = null;
        
        $startFromExpressPage = $this->retrieveStartNodeUnderExpress($currentPageName);
        if (!empty($startFromExpressPage)) {
            $startNodeId = $startFromExpressPage['elementId'];
            Log::info('✅ Using EXPRESS questionnaire-scoped page', ['startNodeId' => $startNodeId, 'page' => $currentPageName]);
        } else {
            // Try to find any page that matches the current page name (case insensitive)
            $pageVariations = $this->findPageVariations($currentPageName);
            if (!empty($pageVariations)) {
                $startNodeId = $pageVariations[0]['elementId'];
                Log::info('✅ Using page variation', ['startNodeId' => $startNodeId, 'page' => $currentPageName, 'foundPage' => $pageVariations[0]['name']]);
            } else {
                // Anchor to EXPRESS questionnaire as fallback
                $expressNode = $this->retrieveExpressComponentNode();
                
                Log::info('🔍 EXPRESS Questionnaire Node Search', [
                    'expressNode' => $expressNode,
                    'currentPageName' => $currentPageName
                ]);

                if (!empty($expressNode)) {
                    $startNodeId = $expressNode['elementId'];
                    Log::info('✅ Using EXPRESS questionnaire node', ['startNodeId' => $startNodeId]);
                } else {
                    Log::warning('⚠️ No suitable start node found');
                }
            }
        }

        // Perform a local search within EXPRESS questionnaire context
        if ($startNodeId) {
            $localResult = $this->localSearch($startNodeId, $embedding, 0.65);
            Log::info('🔍 Local Search Result', ['localResult' => $localResult]);
        } else {
            $localResult = [];
            Log::warning('⚠️ No start node ID, skipping local search');
        }

        // Perform a global search with EXPRESS questionnaire filtering
        if (empty($localResult)) {
            $globalResult = $this->globalSearch($embedding);
            Log::info('🔍 Global Search Result', ['globalResult' => $globalResult]);
            
            // If global search also fails, try a basic search without EXPRESS filtering
            if (empty($globalResult)) {
                Log::warning('⚠️ Both local and global searches failed, trying basic search');
                $globalResult = $this->basicSearch($embedding);
                Log::info('🔍 Basic Search Result', ['basicResult' => $globalResult]);
            }
        } else {
            $globalResult = [];
            Log::info('✅ Local search found results, skipping global search');
        }

        // Determine the target node from local or global search results
        if (!empty($localResult)) {
            $targetNodeId = $localResult['node']['elementId'];
        } elseif (!empty($globalResult)) {
            $targetNodeId = $globalResult[0]['node']['elementId'];        
        } else {
            $targetNodeId = null;
        }

        // Resolve the target node to a Page, Action, or Property node
        if ($targetNodeId) {
            $resolvedNode = $this->resolveTargetNode($targetNodeId);
            $targetNodeId = $resolvedNode['node']['elementId'];
        }

        // Retrieve the target Page node
        if ($targetNodeId) {
            $endNode = $this->retrieveEndNode($targetNodeId);
            if (!empty($endNode) && isset($endNode['page']['elementId'])) {
                $endNodeId = $endNode['page']['elementId'];
            } else {
                Log::warning('⚠️ Could not find page for target node', ['targetNodeId' => $targetNodeId, 'endNode' => $endNode]);
                $endNodeId = null;
            }
        } 

        // Find the shortest path between the current Page and target Page
        if ($startNodeId && $endNodeId && $startNodeId !== $endNodeId) {
            $pathResult = $this->shortestPath($startNodeId, $endNodeId);
            if (!empty($pathResult)) {
                $path = $pathResult['path'];
                $instructions = $pathResult['instructions'];
            }
        } else {
            $pathResult = [];
        }

        // Retrieve comprehensive context from all relevant nodes
        $contextData = $this->retrieveComprehensiveContext($targetNodeId, $endNodeId);
        
        // If we have a target node, also get its immediate neighbors for richer context
        if ($targetNodeId) {
            $questionTypesContext = $this->getQuestionTypesContext($targetNodeId);
            if (!empty($questionTypesContext['content'])) {
                $contextData['content'] = ($contextData['content'] ?? '') . "\n\n" . $questionTypesContext['content'];
                Log::info('Added question types context', ['hasContent' => !empty($questionTypesContext['content'])]);
            }
            
            $neighborContext = $this->getNeighborContext($targetNodeId);
            if (!empty($neighborContext['content'])) {
                $contextData['content'] = ($contextData['content'] ?? '') . "\n\n" . $neighborContext['content'];
                $contextData['relatedNodes'] = array_merge($contextData['relatedNodes'] ?? [], $neighborContext['relatedNodes'] ?? []);
                $contextData['nodeDetails'] = array_merge($contextData['nodeDetails'] ?? [], $neighborContext['nodeDetails'] ?? []);
            }
            
            // Get workflow-specific context for UI guidance
            $workflowContext = $this->getWorkflowContext($targetNodeId);
            if (!empty($workflowContext['content'])) {
                $contextData['content'] = ($contextData['content'] ?? '') . "\n\n" . $workflowContext['content'];
                $contextData['relatedNodes'] = array_merge($contextData['relatedNodes'] ?? [], $workflowContext['relatedNodes'] ?? []);
                $contextData['nodeDetails'] = array_merge($contextData['nodeDetails'] ?? [], $workflowContext['nodeDetails'] ?? []);
            }
        }
        
        return [
            'context' => $contextData['content'] ?? null,
            'path' => $path ?? '',
            'instructions' => $instructions ?? '',
            'relatedNodes' => $contextData['relatedNodes'] ?? [],
            'nodeDetails' => $contextData['nodeDetails'] ?? []
        ];
        });
    }

    /**
     * RETRIEVE COMPREHENSIVE CONTEXT
     * Fetches all relevant content from Chunks, Descriptions, Properties, Options, and other nodes
     * to provide comprehensive context for AI processing
     */
    protected function retrieveComprehensiveContext(string $targetNodeId = null, string $pageNodeId = null): array
    {
        $content = '';
        $relatedNodes = [];
        $nodeDetails = [];

        // If we have a target node, get its content and related nodes
        if ($targetNodeId) {
            $targetContent = $this->getNodeContent($targetNodeId);
            if (!empty($targetContent)) {
                $content .= $targetContent['content'] . "\n\n";
                $relatedNodes[] = $targetContent['node'];
                $nodeDetails[] = $targetContent;
            }

            // Get related Chunks, Descriptions, Properties, Options
            $relatedContent = $this->getRelatedNodeContent($targetNodeId);
            foreach ($relatedContent as $related) {
                if (!empty($related['content'])) {
                    $content .= $related['content'] . "\n\n";
                    $relatedNodes[] = $related['node'];
                    $nodeDetails[] = $related;
                }
            }
        }

        // If we have a page node, get all content from that page
        if ($pageNodeId) {
            $pageContent = $this->getPageContent($pageNodeId);
            foreach ($pageContent as $pageItem) {
                if (!empty($pageItem['content'])) {
                    $content .= $pageItem['content'] . "\n\n";
                    $relatedNodes[] = $pageItem['node'];
                    $nodeDetails[] = $pageItem;
                }
            }
        }

        return [
            'content' => trim($content),
            'relatedNodes' => $relatedNodes,
            'nodeDetails' => $nodeDetails
        ];
    }

    /**
     * GET NODE CONTENT
     * Retrieves content from a specific node based on its type
     */
    protected function getNodeContent(string $elementId): array
    {
        $cypher = <<<CYPHER
        MATCH (n)
        WHERE elementId(n) = \$id
        RETURN n, labels(n) as nodeLabels
        CYPHER;

        $result = $this->runQuery($cypher, ['id' => $elementId]);

        if ($result->count() === 0) {
            return [];
        }

        $node = $result->first()->get('n');
        $labels = $result->first()->get('nodeLabels');
        $properties = $node->getProperties()->toArray();
        $nodeId = $node->getElementId();

        // Convert CypherList to array
        if ($labels instanceof \Laudis\Neo4j\Types\CypherList) {
            $labels = $labels->toArray();
        }

        // Extract content based on node type
        $content = '';
        $nodeType = '';

        if (in_array('Chunk', $labels)) {
            $content = $properties['content'] ?? '';
            $nodeType = 'Chunk';
        } elseif (in_array('Description', $labels)) {
            $content = $properties['name'] ?? '';
            if (!empty($properties['content'])) {
                $content .= ': ' . $properties['content'];
            }
            $nodeType = 'Description';
        } elseif (in_array('Property', $labels)) {
            $content = $properties['name'] ?? '';
            if (!empty($properties['description'])) {
                $content .= ': ' . $properties['description'];
            }
            $nodeType = 'Property';
        } elseif (in_array('Option', $labels)) {
            $content = $properties['name'] ?? '';
            if (!empty($properties['description'])) {
                $content .= ': ' . $properties['description'];
            }
            $nodeType = 'Option';
        } elseif (in_array('Action', $labels)) {
            $content = $properties['name'] ?? '';
            if (!empty($properties['description'])) {
                $content .= ': ' . $properties['description'];
            }
            $nodeType = 'Action';
        } elseif (in_array('Page', $labels)) {
            $content = $properties['name'] ?? '';
            $nodeType = 'Page';
        } elseif (in_array('Prompt', $labels)) {
            $content = $properties['name'] ?? '';
            $nodeType = 'Prompt';
        }

        return [
            'node' => [
                'elementId' => $nodeId,
                'name' => $properties['name'] ?? '',
                'type' => $nodeType,
                'labels' => $labels
            ],
            'content' => $content
        ];
    }

    /**
     * GET RELATED NODE CONTENT
     * Retrieves content from nodes related to the target node
     * Enhanced to get more comprehensive context including multiple hops
     * Updated to follow PROMPTS -> Modal -> HAS_PROPERTY -> HAS_OPTION structure
     */
    protected function getRelatedNodeContent(string $elementId): array
    {
        $cypher = <<<CYPHER
        MATCH (n)
        WHERE elementId(n) = \$id
        
        // Direct relationships (1 hop)
        OPTIONAL MATCH (n)-[r1]-(related1)
        WHERE related1:Chunk OR related1:Description OR related1:Property OR related1:Option OR related1:Action OR related1:Disembodied OR related1:Modal OR related1:Page
        AND type(r1) <> 'HORIZONTAL_TO' AND type(r1) <> 'VERTICAL_TO'
        
        // Follow PROMPTS -> Modal -> HAS_PROPERTY -> Property -> HAS_OPTION -> Option pattern
        OPTIONAL MATCH (n)-[:PROMPTS]->(modal:Modal)-[:HAS_PROPERTY]->(prop:Property)-[:HAS_OPTION]->(opt:Option)
        
        // Follow PROMPTS -> Modal -> HAS_ACTION pattern
        OPTIONAL MATCH (n)-[:PROMPTS]->(modal2:Modal)-[:HAS_ACTION]->(action:Action)
        
        // Follow PROMPTS -> Page -> HAS_PROPERTY -> Property -> HAS_OPTION -> Option pattern
        OPTIONAL MATCH (n)-[:PROMPTS]->(page:Page)-[:HAS_PROPERTY]->(prop2:Property)-[:HAS_OPTION]->(opt2:Option)
        
        // Follow PROMPTS -> Page -> HAS_ACTION pattern
        OPTIONAL MATCH (n)-[:PROMPTS]->(page2:Page)-[:HAS_ACTION]->(action2:Action)
        
        // 2-3 hop neighbors for other related content
        OPTIONAL MATCH path = (n)-[r*2..3]-(related2)
        WHERE related2:Chunk OR related2:Description OR related2:Property OR related2:Option OR related2:Action OR related2:Disembodied OR related2:Modal OR related2:Page
        AND type(r[0]) <> 'HORIZONTAL_TO' AND type(r[0]) <> 'VERTICAL_TO'
        
        WITH collect(DISTINCT related1) + collect(DISTINCT modal) + collect(DISTINCT prop) + collect(DISTINCT opt) + 
             collect(DISTINCT modal2) + collect(DISTINCT action) + collect(DISTINCT page) + collect(DISTINCT prop2) + 
             collect(DISTINCT opt2) + collect(DISTINCT page2) + collect(DISTINCT action2) + collect(DISTINCT related2) as allRelated
        
        UNWIND allRelated as related
        WITH related
        WHERE related IS NOT NULL
        RETURN DISTINCT related, labels(related) as nodeLabels
        LIMIT 50
        CYPHER;

        $result = $this->runQuery($cypher, ['id' => $elementId]);
        $relatedContent = [];

        foreach ($result as $record) {
            $node = $record->get('related');
            $labels = $record->get('nodeLabels');
            $properties = $node->getProperties()->toArray();
            $nodeId = $node->getElementId();

            // Convert CypherList to array
            if ($labels instanceof \Laudis\Neo4j\Types\CypherList) {
                $labels = $labels->toArray();
            }

            // Extract content based on node type
            $content = '';
            $nodeType = '';

            if (in_array('Chunk', $labels)) {
                $content = $properties['content'] ?? '';
                $nodeType = 'Chunk';
            } elseif (in_array('Description', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['content'])) {
                    $content .= ': ' . $properties['content'];
                }
                $nodeType = 'Description';
            } elseif (in_array('Property', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Property';
            } elseif (in_array('Option', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Option';
            } elseif (in_array('Action', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Action';
            } elseif (in_array('Modal', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Modal';
            } elseif (in_array('Page', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Page';
            } elseif (in_array('Disembodied', $labels)) {
                $content = $properties['content'] ?? $properties['name'] ?? '';
                $nodeType = 'Disembodied';
            } elseif (in_array('Prompt', $labels)) {
                $content = $properties['name'] ?? '';
                $nodeType = 'Prompt';
            }

            if (!empty($content)) {
                $relatedContent[] = [
                    'node' => [
                        'elementId' => $nodeId,
                        'name' => $properties['name'] ?? '',
                        'type' => $nodeType,
                        'labels' => $labels
                    ],
                    'content' => $content
                ];
            }
        }

        return $relatedContent;
    }

    /**
     * GET QUESTION TYPES CONTEXT
     * Specifically retrieves all question category options when dealing with question-related prompts
     * This follows the Prompt -> Modal -> Property -> Options structure
     */
    protected function getQuestionTypesContext(string $elementId): array
    {
        $cypher = <<<CYPHER
        MATCH (n)
        WHERE elementId(n) = \$id
        
        // Try multiple paths to find question category options
        // Path 1: Direct from Prompt -> Modal/Page -> Property -> Option
        OPTIONAL MATCH (n)-[:PROMPTS]->(target1)-[:HAS_PROPERTY]->(prop1:Property)-[:HAS_OPTION]->(opt1:Option)
        WHERE toLower(prop1.name) CONTAINS 'question category' OR toLower(prop1.name) CONTAINS 'select question' OR toLower(prop1.name) CONTAINS 'category'
        
        // Path 2: From any node, find nearby Property nodes with "category" in name
        OPTIONAL MATCH (n)-[*1..3]-(prop2:Property)-[:HAS_OPTION]->(opt2:Option)
        WHERE toLower(prop2.name) CONTAINS 'question category' OR toLower(prop2.name) CONTAINS 'select question' OR toLower(prop2.name) CONTAINS 'category'
        
        // Path 3: Broader search - find any Property with question-related Options
        OPTIONAL MATCH (n)-[*1..4]-(modal)-[:HAS_PROPERTY]->(prop3:Property)-[:HAS_OPTION]->(opt3:Option)
        WHERE (modal:Modal OR modal:Page) 
        AND (toLower(modal.name) CONTAINS 'question' OR toLower(modal.name) CONTAINS 'add question')
        AND (toLower(prop3.name) CONTAINS 'category' OR toLower(prop3.name) CONTAINS 'type')
        
        WITH collect(DISTINCT opt1) + collect(DISTINCT opt2) + collect(DISTINCT opt3) as allOptions
        UNWIND allOptions as option
        WITH option
        WHERE option IS NOT NULL
        RETURN DISTINCT option, labels(option) as nodeLabels
        ORDER BY option.name
        CYPHER;

        $result = $this->runQuery($cypher, ['id' => $elementId]);
        
        Log::info('🔍 getQuestionTypesContext executed', [
            'startNodeId' => $elementId,
            'resultCount' => $result->count()
        ]);
        
        if ($result->count() === 0) {
            Log::warning('⚠️ No question type options found', ['startNodeId' => $elementId]);
            return [];
        }

        $questionTypes = [];
        foreach ($result as $record) {
            $node = $record->get('option');
            $properties = $node->getProperties()->toArray();
            
            Log::info('🔍 Option node found', [
                'name' => $properties['name'] ?? 'unknown',
                'hasContent' => !empty($properties['content'] ?? ''),
                'hasDescription' => !empty($properties['description'] ?? '')
            ]);
            
            $name = $properties['name'] ?? '';
            // Check for both 'content' and 'description' properties
            $description = $properties['content'] ?? $properties['description'] ?? '';
            
            if (!empty($name) && !empty($description)) {
                $questionTypes[] = $name . ': ' . $description;
            } elseif (!empty($name)) {
                $questionTypes[] = $name;
            }
        }

        Log::info('✅ Question types extracted', ['count' => count($questionTypes)]);

        if (empty($questionTypes)) {
            return [];
        }

        // Format the content nicely
        $content = "You can create the following kinds of questions in EXPRESS:\n\n";
        $content .= implode("\n\n", $questionTypes);

        return [
            'content' => $content,
            'relatedNodes' => [],
            'nodeDetails' => []
        ];
    }

    /**
     * GET NEIGHBOR CONTEXT
     * Gets immediate neighbors of a node for richer context
     */
    protected function getNeighborContext(string $elementId): array
    {
        $cypher = <<<CYPHER
        MATCH (n)
        WHERE elementId(n) = \$id
        MATCH (n)-[r]-(neighbor)
        WHERE neighbor:Chunk OR neighbor:Description OR neighbor:Property OR neighbor:Option OR neighbor:Action OR neighbor:Disembodied OR neighbor:Modal OR neighbor:Page
        RETURN neighbor, labels(neighbor) as nodeLabels, type(r) as relationshipType
        ORDER BY relationshipType
        LIMIT 15
        CYPHER;

        $result = $this->runQuery($cypher, ['id' => $elementId]);
        $neighborContent = [];
        $relatedNodes = [];
        $nodeDetails = [];

        foreach ($result as $record) {
            $node = $record->get('neighbor');
            $labels = $record->get('nodeLabels');
            $relationshipType = $record->get('relationshipType');
            $properties = $node->getProperties()->toArray();
            $nodeId = $node->getElementId();

            // Convert CypherList to array
            if ($labels instanceof \Laudis\Neo4j\Types\CypherList) {
                $labels = $labels->toArray();
            }

            // Extract content based on node type
            $content = '';
            $nodeType = '';

            if (in_array('Chunk', $labels)) {
                $content = $properties['content'] ?? '';
                $nodeType = 'Chunk';
            } elseif (in_array('Description', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['content'])) {
                    $content .= ': ' . $properties['content'];
                }
                $nodeType = 'Description';
            } elseif (in_array('Property', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Property';
            } elseif (in_array('Option', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Option';
            } elseif (in_array('Action', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Action';
            } elseif (in_array('Modal', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Modal';
            } elseif (in_array('Page', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Page';
            } elseif (in_array('Disembodied', $labels)) {
                $content = $properties['content'] ?? $properties['name'] ?? '';
                $nodeType = 'Disembodied';
            } elseif (in_array('Prompt', $labels)) {
                $content = $properties['name'] ?? '';
                $nodeType = 'Prompt';
            }

            if (!empty($content)) {
                $neighborContent[] = $content;
                $relatedNodes[] = [
                    'elementId' => $nodeId,
                    'name' => $properties['name'] ?? '',
                    'type' => $nodeType,
                    'labels' => $labels,
                    'relationshipType' => $relationshipType
                ];
                $nodeDetails[] = [
                    'node' => [
                        'elementId' => $nodeId,
                        'name' => $properties['name'] ?? '',
                        'type' => $nodeType,
                        'labels' => $labels,
                        'relationshipType' => $relationshipType
                    ],
                    'content' => $content
                ];
            }
        }

        return [
            'content' => implode("\n\n", $neighborContent),
            'relatedNodes' => $relatedNodes,
            'nodeDetails' => $nodeDetails
        ];
    }

    /**
     * GET WORKFLOW CONTEXT
     * Gets workflow-specific context including UI actions, modals, and step-by-step guidance
     */
    protected function getWorkflowContext(string $elementId): array
    {
        $cypher = <<<CYPHER
        MATCH (n)
        WHERE elementId(n) = \$id
        MATCH (n)-[r*1..2]-(workflow)
        WHERE workflow:Action OR workflow:Modal OR workflow:Property OR workflow:Option
        AND (workflow.name CONTAINS 'add' OR workflow.name CONTAINS 'button' OR workflow.name CONTAINS 'modal' 
             OR workflow.name CONTAINS 'click' OR workflow.name CONTAINS 'open' OR workflow.name CONTAINS 'step'
             OR workflow.description CONTAINS 'add' OR workflow.description CONTAINS 'button' OR workflow.description CONTAINS 'modal'
             OR workflow.description CONTAINS 'click' OR workflow.description CONTAINS 'open' OR workflow.description CONTAINS 'step')
        RETURN workflow, labels(workflow) as nodeLabels, type(r[0]) as relationshipType
        ORDER BY relationshipType
        LIMIT 10
        CYPHER;

        $result = $this->runQuery($cypher, ['id' => $elementId]);
        $workflowContent = [];
        $relatedNodes = [];
        $nodeDetails = [];

        foreach ($result as $record) {
            $node = $record->get('workflow');
            $labels = $record->get('nodeLabels');
            $relationshipType = $record->get('relationshipType');
            $properties = $node->getProperties()->toArray();
            $nodeId = $node->getElementId();

            // Convert CypherList to array
            if ($labels instanceof \Laudis\Neo4j\Types\CypherList) {
                $labels = $labels->toArray();
            }

            // Extract content based on node type
            $content = '';
            $nodeType = '';

            if (in_array('Action', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Action';
            } elseif (in_array('Modal', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Modal';
            } elseif (in_array('Property', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Property';
            } elseif (in_array('Option', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Option';
            }

            if (!empty($content)) {
                $workflowContent[] = $content;
                $relatedNodes[] = [
                    'elementId' => $nodeId,
                    'name' => $properties['name'] ?? '',
                    'type' => $nodeType,
                    'labels' => $labels,
                    'relationshipType' => $relationshipType
                ];
                $nodeDetails[] = [
                    'node' => [
                        'elementId' => $nodeId,
                        'name' => $properties['name'] ?? '',
                        'type' => $nodeType,
                        'labels' => $labels,
                        'relationshipType' => $relationshipType
                    ],
                    'content' => $content
                ];
            }
        }

        return [
            'content' => implode("\n\n", $workflowContent),
            'relatedNodes' => $relatedNodes,
            'nodeDetails' => $nodeDetails
        ];
    }

    /**
     * GET PAGE CONTENT
     * Retrieves all content from a page and its connected nodes
     */
    protected function getPageContent(string $pageElementId): array
    {
        $cypher = <<<CYPHER
        MATCH (p:Page)
        WHERE elementId(p) = \$id
        MATCH (p)-[r]-(related)
        WHERE related:Chunk OR related:Description OR related:Property OR related:Option OR related:Action
        RETURN related, labels(related) as nodeLabels, type(r) as relationshipType
        CYPHER;

        $result = $this->runQuery($cypher, ['id' => $pageElementId]);
        $pageContent = [];

        foreach ($result as $record) {
            $node = $record->get('related');
            $labels = $record->get('nodeLabels');
            $relationshipType = $record->get('relationshipType');
            $properties = $node->getProperties()->toArray();
            $nodeId = $node->getElementId();

            // Convert CypherList to array
            if ($labels instanceof \Laudis\Neo4j\Types\CypherList) {
                $labels = $labels->toArray();
            }

            // Extract content based on node type
            $content = '';
            $nodeType = '';

            if (in_array('Chunk', $labels)) {
                $content = $properties['content'] ?? '';
                $nodeType = 'Chunk';
            } elseif (in_array('Description', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['content'])) {
                    $content .= ': ' . $properties['content'];
                }
                $nodeType = 'Description';
            } elseif (in_array('Property', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Property';
            } elseif (in_array('Option', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Option';
            } elseif (in_array('Action', $labels)) {
                $content = $properties['name'] ?? '';
                if (!empty($properties['description'])) {
                    $content .= ': ' . $properties['description'];
                }
                $nodeType = 'Action';
            }

            if (!empty($content)) {
                $pageContent[] = [
                    'node' => [
                        'elementId' => $nodeId,
                        'name' => $properties['name'] ?? '',
                        'type' => $nodeType,
                        'labels' => $labels,
                        'relationshipType' => $relationshipType
                    ],
                    'content' => $content
                ];
            }
        }

        return $pageContent;
    }

    /**
     * RETRIEVE EXPRESS QUESTIONNAIRE NODE
     * Fetches the EXPRESS questionnaire node's element ID for context-based searching
     * Updated to handle both Page and Component node types
     */
    public function retrieveExpressComponentNode(): array
    {
        $cypher = <<<CYPHER
        MATCH (c)
        WHERE (c:Page OR c:Component) 
        AND (c.name = 'EXPRESS' OR c.name = 'EXPRESS questionnaire' OR toLower(c.name) CONTAINS 'express questionnaire')
        RETURN c AS component;
        CYPHER;

        $result = $this->runQuery($cypher);

        if ($result->count() === 0) {
            return [];
        }

        // Fetch node ID
        $expressNodeElementId = $result->first()->get('component')->getElementId();

        return ['elementId' => $expressNodeElementId];
    }

    /**
     * RETRIEVE START NODE
     * Fetches the starting Page node's element ID based on the provided page name
     */
    public function retrieveStartNode(string $pageName): array
    {
        $cypher = <<<CYPHER
        MATCH (p:Page {name: \$pageName})
        RETURN p AS page;
        CYPHER;

        $result = $this->runQuery($cypher, [
            'pageName' => $pageName
        ]);

        if ($result->count() === 0) {
            return [];
        }

        // Fetch node ID
        $startNodeElementId = $result->first()->get('page')->getElementId();

        return ['elementId'=>$startNodeElementId];
    }

    /**
     * RETRIEVE START NODE UNDER EXPRESS
     * Finds a Page by name within EXPRESS questionnaire scope
     * Updated to handle both Component and Page node types for EXPRESS
     */
    protected function retrieveStartNodeUnderExpress(string $pageName): array
    {
        $cypher = <<<CYPHER
        MATCH (c)
        WHERE (c:Component OR c:Page)
        AND (c.name = 'EXPRESS' OR c.name = 'EXPRESS questionnaire' OR toLower(c.name) CONTAINS 'express questionnaire')
        MATCH (c)-[:HORIZONTAL_TO|VERTICAL_TO]->(p:Page)
        WHERE toLower(p.name) = toLower(\$pageName)
        RETURN p AS page
        LIMIT 1;
        CYPHER;

        $result = $this->runQuery($cypher, [
            'pageName' => $pageName
        ]);

        if ($result->count() === 0) {
            return [];
        }

        $startNodeElementId = $result->first()->get('page')->getElementId();
        return ['elementId' => $startNodeElementId];
    }

    /**
     * FIND PAGE VARIATIONS
     * Finds pages with similar names using fuzzy matching
     */
    protected function findPageVariations(string $pageName): array
    {
        $cypher = <<<CYPHER
        MATCH (p:Page)
        WHERE toLower(p.name) CONTAINS toLower(\$pageName)
        OR toLower(\$pageName) CONTAINS toLower(p.name)
        OR p.name =~ ('(?i).*' + \$pageName + '.*')
        RETURN p AS page
        ORDER BY 
            CASE WHEN toLower(p.name) = toLower(\$pageName) THEN 1
                 WHEN toLower(p.name) CONTAINS toLower(\$pageName) THEN 2
                 ELSE 3 END,
            p.name
        LIMIT 5;
        CYPHER;

        $result = $this->runQuery($cypher, [
            'pageName' => $pageName
        ]);

        $pages = [];
        foreach ($result as $record) {
            $node = $record->get('page');
            $properties = $node->getProperties()->toArray();
            $pages[] = [
                'elementId' => $node->getElementId(),
                'name' => $properties['name'] ?? ''
            ];
        }

        return $pages;
    }

    /**
     * LOCAL SEARCH 
     * Searches within EXPRESS questionnaire context for relevant information.
     * Enhanced to get multiple relevant nodes and better context
     */
    protected function localSearch(string $elementId, array $embedding, float $minScore = 0.65): array
    {
        $cypher = <<<CYPHER
        MATCH (start)
        WHERE elementId(start) = \$elementId

        // If starting from EXPRESS questionnaire, search within its context
        OPTIONAL MATCH (start)
        WHERE (start:Page) 
        AND (start.name = 'EXPRESS' OR start.name = 'EXPRESS questionnaire' OR toLower(start.name) CONTAINS 'express questionnaire')
        WITH start, CASE WHEN start IS NOT NULL THEN true ELSE false END AS isExpressQuestionnaire

        // If EXPRESS questionnaire, search within its connected nodes
        OPTIONAL MATCH (start)-[r1]-(n1)
        WHERE isExpressQuestionnaire = true 
        AND type(r1) <> 'HORIZONTAL_TO' AND type(r1) <> 'VERTICAL_TO'
        WITH start, collect(n1) AS hop1, isExpressQuestionnaire

        // If not EXPRESS questionnaire, fall back to original search
        OPTIONAL MATCH (start:Page)
        WHERE isExpressQuestionnaire = false
        OPTIONAL MATCH (start)-[r1]-(n1)
        WHERE type(r1) <> 'HORIZONTAL_TO' AND type(r1) <> 'VERTICAL_TO'
        WITH start, collect(n1) AS hop1, isExpressQuestionnaire

        // 2-hop neighbors
        UNWIND hop1 AS n1
        OPTIONAL MATCH (n1)-[r2]-(n2)
        WHERE type(r2) <> 'HORIZONTAL_TO' AND type(r2) <> 'VERTICAL_TO'
        WITH hop1, collect(n2) AS hop2, isExpressQuestionnaire

        // 3-hop neighbors
        UNWIND hop2 AS n2
        OPTIONAL MATCH (n2)-[r3]-(n3)
        WHERE type(r3) <> 'HORIZONTAL_TO' AND type(r3) <> 'VERTICAL_TO'
        WITH hop1, hop2, collect(n3) AS hop3, isExpressQuestionnaire

        // 4-hop neighbors
        UNWIND hop3 AS n3
        OPTIONAL MATCH (n3)-[r4]-(n4)
        WHERE type(r4) <> 'HORIZONTAL_TO' AND type(r4) <> 'VERTICAL_TO'
        WITH hop1, hop2, hop3, collect(n4) AS hop4, isExpressQuestionnaire

        // Flatten all hops into one list
        WITH apoc.coll.toSet(apoc.coll.flatten([hop1, hop2, hop3, hop4])) AS candidates, isExpressQuestionnaire
        UNWIND candidates AS c
        WITH c, isExpressQuestionnaire
        WHERE c IS NOT NULL AND c.embedding IS NOT NULL AND size(c.embedding) > 0
        // Prioritize EXPRESS-related nodes when searching from EXPRESS questionnaire
        AND (isExpressQuestionnaire = false OR c.name CONTAINS 'EXPRESS' OR c:Action OR c:Property OR c:Page OR c:Modal OR c:Option OR c:Description OR c:Prompt OR c:Chunk OR c:Disembodied)
        RETURN c AS node
        CYPHER;

        $candidates = $this->runQuery($cypher, [
            'elementId' => $elementId,
        ]);

        if ($candidates->count() === 0) {
            return [];
        }

        // Compute cosine similarity and find multiple good matches
        $results = [];
        foreach ($candidates as $candidate) {
            $node = $candidate->get('node')->getProperties()->toArray();
            $node['elementId'] = $candidate->get('node')->getElementId();
            $candidateEmbedding = $node['embedding'];
            if (!$candidateEmbedding || count($candidateEmbedding) === 0) {
                continue;
            }

            $dot = 0.0;
            $normA = 0.0;
            $normB = 0.0;

            foreach ($embedding as $i => $value) {
                $dot += $value * $candidateEmbedding[$i];
                $normA += $value * $value;
                $normB += $candidateEmbedding[$i] * $candidateEmbedding[$i];
            }

            $score = $dot / (sqrt($normA) * sqrt($normB));

            if ($score >= $minScore) {
                $results[] = [
                    'node' => $node,
                    'score' => $score
                ];
            }
        }

        // Sort by score and return the best match
        usort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $bestResult = !empty($results) ? $results[0] : null;
        
        if ($bestResult) {
            Log::info('🔍 Local Search Best Match', [
                'nodeName' => $bestResult['node']['name'] ?? 'unknown',
                'score' => $bestResult['score'],
                'totalCandidates' => count($results)
            ]);
        }

        return $bestResult ?: [];
    }

    /**
     * GLOBAL SEARCH 
     * Searches all nodes containing embeddings in the graph for relevant information.
     * Prioritizes EXPRESS questionnaire related nodes for better context.
     * Uses cosine similarity to find the best matching node based on the provided embedding.
     * Returns a list of n nodes depending on the $limit variable - no thresholding is applied here, but a $minScore variable is set if you would like to apply one
     */
    protected function globalSearch(array $embedding, int $limit = 5, float $minScore = 0): array
    {
        $cypher = <<<CYPHER
        CALL () {
            CALL db.index.vector.queryNodes('action_embedding_idx', \$limit, \$embedding)
            YIELD node, score
            RETURN node, score, 
                   CASE WHEN node.name CONTAINS 'EXPRESS' OR (node)-[:BELONGS_TO]->(:Component {name: 'EXPRESS questionnaire'}) THEN 1.0 ELSE 0.5 END AS expressPriority
            UNION ALL
            CALL db.index.vector.queryNodes('property_embedding_idx', \$limit, \$embedding)
            YIELD node, score
            RETURN node, score,
                   CASE WHEN node.name CONTAINS 'EXPRESS' OR (node)-[:BELONGS_TO]->(:Component {name: 'EXPRESS questionnaire'}) THEN 1.0 ELSE 0.5 END AS expressPriority
            UNION ALL
            CALL db.index.vector.queryNodes('prompt_embedding_idx', \$limit, \$embedding)
            YIELD node, score
            RETURN node, score,
                   CASE WHEN node.name CONTAINS 'EXPRESS' OR (node)-[:BELONGS_TO]->(:Component {name: 'EXPRESS questionnaire'}) THEN 1.0 ELSE 0.5 END AS expressPriority
            UNION ALL
            CALL db.index.vector.queryNodes('description_embedding_idx', \$limit, \$embedding)
            YIELD node, score
            RETURN node, score,
                   CASE WHEN node.name CONTAINS 'EXPRESS' OR (node)-[:BELONGS_TO]->(:Component {name: 'EXPRESS questionnaire'}) THEN 1.0 ELSE 0.5 END AS expressPriority
            UNION ALL
            CALL db.index.vector.queryNodes('modal_embedding_idx', \$limit, \$embedding)
            YIELD node, score
            RETURN node, score,
                   CASE WHEN node.name CONTAINS 'EXPRESS' OR (node)-[:BELONGS_TO]->(:Component {name: 'EXPRESS questionnaire'}) THEN 1.0 ELSE 0.5 END AS expressPriority
            UNION ALL
            CALL db.index.vector.queryNodes('option_embedding_idx', \$limit, \$embedding)
            YIELD node, score
            RETURN node, score,
                   CASE WHEN node.name CONTAINS 'EXPRESS' OR (node)-[:BELONGS_TO]->(:Component {name: 'EXPRESS questionnaire'}) THEN 1.0 ELSE 0.5 END AS expressPriority
            UNION ALL
            CALL db.index.vector.queryNodes('chunk_embedding_idx', \$limit, \$embedding)
            YIELD node, score
            RETURN node, score,
                   CASE WHEN node.name CONTAINS 'EXPRESS' OR (node)-[:BELONGS_TO]->(:Component {name: 'EXPRESS questionnaire'}) THEN 1.0 ELSE 0.5 END AS expressPriority
            UNION ALL
            CALL db.index.vector.queryNodes('disembodied_embedding_idx', \$limit, \$embedding)
            YIELD node, score
            RETURN node, score,
                   CASE WHEN node.name CONTAINS 'EXPRESS' OR (node)-[:BELONGS_TO]->(:Component {name: 'EXPRESS questionnaire'}) THEN 1.0 ELSE 0.5 END AS expressPriority
        }
        WITH node, score, expressPriority
        WHERE score >= \$minScore
        RETURN node, score
        ORDER BY (score * expressPriority) DESC, score DESC
        LIMIT \$limit;
        CYPHER;

        $result = $this->runQuery($cypher, [
            'embedding' => $embedding,
            'limit' => $limit,
            'minScore' => $minScore,
        ]);

        if ($result->count() === 0) {
            return [];
        }

        // Normalise results so similar format to local search

        $normalised = [];
        foreach ($result as $record) {
            $node = $record->get('node')->getProperties()->toArray();
            $node['elementId'] = $record->get('node')->getElementId();

            $score = $record->get('score');

            $normalised[] = [
                'node' => $node,
                'score' => $score
            ];
        }

        info($normalised);

        return $normalised;
    }

    /**
     * BASIC SEARCH 
     * Fallback search that looks for any nodes with embeddings, without EXPRESS filtering
     */
    public function checkPromptMatch(array $embedding, float $minScore = 0.85): ?array
    {
        $cypher = <<<CYPHER
        CALL db.index.vector.queryNodes('prompt_embedding_idx', 3, \$embedding)
        YIELD node, score
        WHERE score >= \$minScore
        AND (node)-[:BELONGS_TO]->(:Component {name: 'EXPRESS questionnaire'})
        RETURN node, score
        ORDER BY score DESC
        LIMIT 1;
        CYPHER;

        $result = $this->runQuery($cypher, [
            'embedding' => $embedding,
            'minScore' => $minScore,
        ]);

        if ($result->count() === 0) {
            return null;
        }

        $record = $result->first();
        $node = $record->get('node')->getProperties()->toArray();
        $node['elementId'] = $record->get('node')->getElementId();
        $score = $record->get('score');

        return [
            'node' => $node,
            'score' => $score
        ];
    }

    protected function basicSearch(array $embedding, int $limit = 5, float $minScore = 0.1): array
    {
        $cypher = <<<CYPHER
        MATCH (n)
        WHERE n.embedding IS NOT NULL AND size(n.embedding) > 0
        AND (n:Action OR n:Property OR n:Description OR n:Prompt OR n:Chunk OR n:Modal OR n:Option OR n:Disembodied OR n:Page)
        RETURN n AS node
        LIMIT 100
        CYPHER;

        $candidates = $this->runQuery($cypher);

        if ($candidates->count() === 0) {
            return [];
        }

        // Compute cosine similarity and find the best matches
        $results = [];
        foreach ($candidates as $candidate) {
            $node = $candidate->get('node')->getProperties()->toArray();
            $node['elementId'] = $candidate->get('node')->getElementId();
            $candidateEmbedding = $node['embedding'];
            
            if (!$candidateEmbedding || count($candidateEmbedding) === 0) {
                continue;
            }

            $dot = 0.0;
            $normA = 0.0;
            $normB = 0.0;

            foreach ($embedding as $i => $value) {
                $dot += $value * $candidateEmbedding[$i];
                $normA += $value * $value;
                $normB += $candidateEmbedding[$i] * $candidateEmbedding[$i];
            }

            $score = $dot / (sqrt($normA) * sqrt($normB));

            if ($score >= $minScore) {
                $results[] = [
                    'node' => $node,
                    'score' => $score
                ];
            }
        }

        // Sort by score and return top results
        usort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * RESOLVE TARGET
     * Takes the node ID and returns the associated Page, Action or Property node
     * Prioritizes EXPRESS questionnaire related nodes
     */
    protected function resolveTargetNode(string $elementId): array
    {
        $cypher = <<<CYPHER
        MATCH (n)
        WHERE elementId(n) = \$id
        CALL (n) {
            // Case 1: already a Page, Action, Property, Modal, or Option
            WITH n
            WITH n WHERE n:Page OR n:Action OR n:Property OR n:Modal OR n:Option
            RETURN n AS target

            UNION 

            // Case 2: Node is a Description
            WITH n
            WITH n WHERE n:Description
            MATCH (n)-[:DESCRIBES]->(m:Action|Property|Page|Modal|Option)
            RETURN m AS target

            UNION
 
            // Case 3: Node is a Prompt - can point to various node types
            WITH n
            WITH n WHERE n:Prompt
            MATCH (n)-[:PROMPTS]->(m)
            WHERE m:Action OR m:Property OR m:Page OR m:Modal OR m:Option
            RETURN m AS target
            
            UNION
            
            // Case 3b: Prompt that should return itself for special handling (question types, etc.)
            WITH n
            WITH n WHERE n:Prompt
            AND (toLower(n.name) CONTAINS 'what kinds of questions' OR toLower(n.name) CONTAINS 'what types of questions' 
                 OR toLower(n.name) CONTAINS 'question categories' OR toLower(n.name) CONTAINS 'question types')
            RETURN n AS target

            UNION

            // Case 4: Node is a Chunk
            WITH n
            WITH n WHERE n:Chunk
            MATCH (n)<-[:IN_CHUNK]-(m:Action|Property|Page|Modal|Option)
            RETURN m AS target

            UNION

            // Case 5: Node is Disembodied - return itself
            WITH n
            WITH n WHERE n:Disembodied
            RETURN n AS target

            UNION

            // Case 6: Node is EXPRESS Questionnaire - return itself
            WITH n
            WITH n WHERE n:Component AND n.name = 'EXPRESS questionnaire'
            RETURN n AS target
        }
        RETURN target
        LIMIT 1;
        CYPHER;

        $result = $this->runQuery($cypher, [
            'id' => $elementId
        ]);

        if ($result->count() === 0) {
            return [];
        }

        $node = $result->first()->get('target')->getProperties()->toArray();
        $node['elementId'] = $result->first()->get('target')->getElementId();

        info($node);

        return ['node' => $node];
    }

    /**
     * RETRIEVE END NODE
     * Takes the node ID of the Page, Action, Property, or Component and returns the associated Page
     * Handles EXPRESS questionnaire nodes by finding related pages
     */
    protected function retrieveEndNode(string $elementId): array
    {
        $cypher = <<<CYPHER
        MATCH (n)
        WHERE elementId(n) = \$id
        CALL (n) {
            // Case 1: node is a Page
            WITH n
            WITH n WHERE n:Page
            RETURN n AS page

            UNION

            // Case 2: node is an Action 
            WITH n
            WITH n WHERE n:Action 
            MATCH (p:Page)-[:HAS_ACTION]->(n)
            RETURN p AS page

            UNION

            // Case 3: node is a Property
            WITH n
            WITH n WHERE n:Property
            MATCH (p:Page)-[:HAS_PROPERTY]->(n)
            RETURN p AS page

            UNION

            // Case 3b: Property node connected to Modal - find page through modal
            WITH n
            WITH n WHERE n:Property
            MATCH (modal:Modal)-[:HAS_PROPERTY]->(n)
            MATCH (a:Action)-[:OPENS_MODAL]->(modal)
            MATCH (p:Page)-[:HAS_ACTION]->(a)
            RETURN p AS page

            UNION

            // Case 4: node is a Modal
            WITH n
            WITH n WHERE n:Modal
            MATCH (a:Action)-[:OPENS_MODAL]->(n)
            MATCH (p:Page)-[:HAS_ACTION]->(a)
            RETURN p AS page

            UNION

            // Case 5: node is an Option
            WITH n
            WITH n WHERE n:Option
            MATCH (prop:Property)-[:HAS_OPTION]->(n)
            MATCH (p:Page)-[:HAS_PROPERTY]->(prop)
            RETURN p AS page

            UNION

            // Case 6: node is a Description - direct paths
            WITH n
            WITH n WHERE n:Description
            MATCH (n)-[:DESCRIBES]->(target:Page)
            RETURN target AS page

            UNION

            // Case 6b: Description -> Action -> Page
            WITH n
            WITH n WHERE n:Description
            MATCH (n)-[:DESCRIBES]->(target:Action)
            MATCH (p:Page)-[:HAS_ACTION]->(target)
            RETURN p AS page

            UNION

            // Case 6c: Description -> Property -> Page
            WITH n
            WITH n WHERE n:Description
            MATCH (n)-[:DESCRIBES]->(target:Property)
            MATCH (p:Page)-[:HAS_PROPERTY]->(target)
            RETURN p AS page

            UNION

            // Case 6d: Description -> Modal -> Action -> Page
            WITH n
            WITH n WHERE n:Description
            MATCH (n)-[:DESCRIBES]->(target:Modal)
            MATCH (a:Action)-[:OPENS_MODAL]->(target)
            MATCH (p:Page)-[:HAS_ACTION]->(a)
            RETURN p AS page

            UNION

            // Case 6e: Description -> Option -> Property -> Page
            WITH n
            WITH n WHERE n:Description
            MATCH (n)-[:DESCRIBES]->(target:Option)
            MATCH (prop:Property)-[:HAS_OPTION]->(target)
            MATCH (p:Page)-[:HAS_PROPERTY]->(prop)
            RETURN p AS page

            UNION

            // Case 6f: Description -> Option -> Property -> Modal -> Action -> Page
            WITH n
            WITH n WHERE n:Description
            MATCH (n)-[:DESCRIBES]->(target:Option)
            MATCH (modal:Modal)-[:HAS_PROPERTY]->(prop:Property)-[:HAS_OPTION]->(target)
            MATCH (a:Action)-[:OPENS_MODAL]->(modal)
            MATCH (p:Page)-[:HAS_ACTION]->(a)
            RETURN p AS page

            UNION

            // Case 7: node is a Prompt - direct paths
            WITH n
            WITH n WHERE n:Prompt
            MATCH (n)-[:PROMPTS]->(target:Page)
            RETURN target AS page

            UNION

            // Case 7b: Prompt -> Action -> Page
            WITH n
            WITH n WHERE n:Prompt
            MATCH (n)-[:PROMPTS]->(target:Action)
            MATCH (p:Page)-[:HAS_ACTION]->(target)
            RETURN p AS page

            UNION

            // Case 7c: Prompt -> Property -> Page
            WITH n
            WITH n WHERE n:Prompt
            MATCH (n)-[:PROMPTS]->(target:Property)
            MATCH (p:Page)-[:HAS_PROPERTY]->(target)
            RETURN p AS page

            UNION

            // Case 7d: Prompt -> Modal -> Action -> Page
            WITH n
            WITH n WHERE n:Prompt
            MATCH (n)-[:PROMPTS]->(target:Modal)
            MATCH (a:Action)-[:OPENS_MODAL]->(target)
            MATCH (p:Page)-[:HAS_ACTION]->(a)
            RETURN p AS page

            UNION

            // Case 7e: Prompt -> Option -> Property -> Page
            WITH n
            WITH n WHERE n:Prompt
            MATCH (n)-[:PROMPTS]->(target:Option)
            MATCH (prop:Property)-[:HAS_OPTION]->(target)
            MATCH (p:Page)-[:HAS_PROPERTY]->(prop)
            RETURN p AS page

            UNION

            // Case 8: node is a Chunk - direct paths
            WITH n
            WITH n WHERE n:Chunk
            MATCH (n)<-[:IN_CHUNK]-(target:Page)
            RETURN target AS page

            UNION

            // Case 8b: Chunk <- Action -> Page
            WITH n
            WITH n WHERE n:Chunk
            MATCH (n)<-[:IN_CHUNK]-(target:Action)
            MATCH (p:Page)-[:HAS_ACTION]->(target)
            RETURN p AS page

            UNION

            // Case 8c: Chunk <- Property -> Page
            WITH n
            WITH n WHERE n:Chunk
            MATCH (n)<-[:IN_CHUNK]-(target:Property)
            MATCH (p:Page)-[:HAS_PROPERTY]->(target)
            RETURN p AS page

            UNION

            // Case 8d: Chunk <- Modal -> Action -> Page
            WITH n
            WITH n WHERE n:Chunk
            MATCH (n)<-[:IN_CHUNK]-(target:Modal)
            MATCH (a:Action)-[:OPENS_MODAL]->(target)
            MATCH (p:Page)-[:HAS_ACTION]->(a)
            RETURN p AS page

            UNION

            // Case 8e: Chunk <- Option -> Property -> Page
            WITH n
            WITH n WHERE n:Chunk
            MATCH (n)<-[:IN_CHUNK]-(target:Option)
            MATCH (prop:Property)-[:HAS_OPTION]->(target)
            MATCH (p:Page)-[:HAS_PROPERTY]->(prop)
            RETURN p AS page

            UNION

            // Case 9: node is Disembodied - find related pages through connections
            WITH n
            WITH n WHERE n:Disembodied
            OPTIONAL MATCH (n)-[r]-(related:Page)
            WITH n, related
            WHERE related IS NOT NULL
            RETURN related AS page

            UNION

            // Case 9b: Disembodied -> Action -> Page
            WITH n
            WITH n WHERE n:Disembodied
            OPTIONAL MATCH (n)-[r]-(related:Action)
            MATCH (p:Page)-[:HAS_ACTION]->(related)
            RETURN p AS page

            UNION

            // Case 9c: Disembodied -> Property -> Page
            WITH n
            WITH n WHERE n:Disembodied
            OPTIONAL MATCH (n)-[r]-(related:Property)
            MATCH (p:Page)-[:HAS_PROPERTY]->(related)
            RETURN p AS page

            UNION

            // Case 10: node is EXPRESS Questionnaire - find related pages
            WITH n
            WITH n WHERE n:Component AND n.name = 'EXPRESS questionnaire'
            OPTIONAL MATCH (n)-[:HAS_ACTION|HAS_PROPERTY|HORIZONTAL_TO|VERTICAL_TO]->(related)
            OPTIONAL MATCH (p:Page)-[:HAS_ACTION|HAS_PROPERTY|HORIZONTAL_TO|VERTICAL_TO]->(related)
            WITH n, p
            WHERE p IS NOT NULL
            RETURN p AS page
        }
        RETURN page;
        CYPHER;

        $result = $this->runQuery($cypher, [
            'id' => $elementId
        ]);

        Log::info('🔍 retrieveEndNode query result', [
            'elementId' => $elementId,
            'resultCount' => $result->count()
        ]);

        if ($result->count() === 0) {
            Log::warning('⚠️ No page found for elementId', ['elementId' => $elementId]);
            return [];
        }

        $node = $result->first()->get('page')->getProperties()->toArray();
        $node['elementId'] = $result->first()->get('page')->getElementId();

        Log::info('✅ Found page for elementId', [
            'elementId' => $elementId,
            'pageName' => $node['name'] ?? 'unknown',
            'pageElementId' => $node['elementId']
        ]);

        return ['page' => $node];
    }

    /**
     * SHORTEST PATH
     * Finds the shortest path between the user's current Page node and their target Page node
     * Fetches instructions for each step in the path and returns them in order
     */
    protected function shortestPath(string $startNodeId, string $endNodeId): array
    {
        $cypher = <<<CYPHER
        MATCH (start:Page), (end:Page)
        WHERE elementId(start) = \$startId AND elementId(end) = \$endId
        MATCH path = shortestPath((start)-[r:VERTICAL_TO|HORIZONTAL_TO*]-(end))
        RETURN 
            [rel in relationships(path) | rel.instruction] AS instructions, 
            [node in nodes(path) | node.name] AS pathNodes;
        CYPHER;

        $result = $this->runQuery($cypher, [
            'startId' => $startNodeId,
            'endId' => $endNodeId
        ]);

        if ($result->count() === 0) {
            return [];
        }

        $record = $result->first();

        $instructions = $record->get('instructions');
        if ($instructions instanceof \Laudis\Neo4j\Types\CypherList) {
            $instructions = $instructions->toArray();
        }
        
        $pathNodes = $record->get('pathNodes');
        if ($pathNodes instanceof \Laudis\Neo4j\Types\CypherList) {
            $pathNodes = $pathNodes->toArray();
        } 

        $instructionStr = '';
        foreach ($instructions as $i => $instr) {
            $instructionStr .= "Step " . ($i + 1) . ": " . $instr . "\n";
        }

        $pathStr = implode('->', $pathNodes);

        info([$instructionStr, $pathStr]);

        return [
            'instructions' => $instructionStr, 
            'path' => $pathStr
        ];
    }

    /**
     * RETRIEVE CHUNK
     * Takes the node ID of the Page, Action, Property, Modal, or Option and returns the associated Chunk
     * Updated to work with new node types
     */
    protected function retrieveChunk(string $elementId): array
    {
        $cypher = <<<CYPHER
        MATCH (n)-[:IN_CHUNK]->(c:Chunk)
        WHERE elementId(n) = \$id
        RETURN c AS chunk
        UNION
        MATCH (n:Chunk)
        WHERE elementId(n) = \$id
        RETURN n AS chunk;
        CYPHER;

        $result = $this->runQuery($cypher, [
            'id' => $elementId
        ]);

        if ($result->count() === 0) {
            return [];
        }

        $node = $result->first()->get('chunk')->getProperties()->toArray();
        $node['elementId'] = $result->first()->get('chunk')->getElementId();

        info($node);

        return ['chunk' => $node];
    }

    /**
     * GET RELATIONSHIP INSTRUCTIONS
     * Helper method to get navigation instructions from relationships
     * Updated to work with new relationship types
     */
    public function getRelationshipInstructions(string $fromNodeId, string $toNodeId): array
    {
        $cypher = <<<CYPHER
        MATCH (from)-[r:VERTICAL_TO|HORIZONTAL_TO]->(to)
        WHERE elementId(from) = \$fromId AND elementId(to) = \$toId
        RETURN r.instruction AS instruction, type(r) AS relationshipType
        UNION
        MATCH (from)-[r:VERTICAL_TO|HORIZONTAL_TO]->(to)
        WHERE elementId(to) = \$fromId AND elementId(from) = \$toId
        RETURN r.instruction AS instruction, type(r) AS relationshipType
        CYPHER;

        $result = $this->runQuery($cypher, [
            'fromId' => $fromNodeId,
            'toId' => $toNodeId
        ]);

        if ($result->count() === 0) {
            return [];
        }

        $instructions = [];
        foreach ($result as $record) {
            $instructions[] = [
                'instruction' => $record->get('instruction'),
                'relationshipType' => $record->get('relationshipType')
            ];
        }

        return $instructions;
    }

    /**
     * GET NODE CONNECTIONS
     * Get all connections for a specific node with their relationship types
     * Useful for debugging and understanding graph structure
     */
    public function getNodeConnections(string $elementId): array
    {
        $cypher = <<<CYPHER
        MATCH (n)-[r]-(connected)
        WHERE elementId(n) = \$id
        RETURN 
            type(r) AS relationshipType,
            labels(connected) AS connectedNodeLabels,
            connected.name AS connectedNodeName,
            elementId(connected) AS connectedNodeId,
            r.instruction AS instruction
        ORDER BY relationshipType, connectedNodeName
        CYPHER;

        $result = $this->runQuery($cypher, [
            'id' => $elementId
        ]);

        $connections = [];
        foreach ($result as $record) {
            $connections[] = [
                'relationshipType' => $record->get('relationshipType'),
                'connectedNodeLabels' => $record->get('connectedNodeLabels'),
                'connectedNodeName' => $record->get('connectedNodeName'),
                'connectedNodeId' => $record->get('connectedNodeId'),
                'instruction' => $record->get('instruction')
            ];
        }

        return $connections;
    }

    public function getExpertContext(array $embedding): array
    {
        Log::info('[Neo4j Expert] getExpertContext called - using expert knowledge base');
        
        // Always run diagnostic checks (not cached) to help debug knowledge base issues
        try {
            $checkCypher = <<<CYPHER
            MATCH (n:Question)
            RETURN count(n) AS total
            CYPHER;
            $checkResult = $this->runQuery($checkCypher);
            $totalQuestions = $checkResult->first()->get('total') ?? 0;
            Log::info('[Neo4j Expert] Total Question nodes in database', ['count' => $totalQuestions]);
            
            // Check how many have embeddings
            $embeddingCheckCypher = <<<CYPHER
            MATCH (n:Question)
            WHERE n.embedding IS NOT NULL AND size(n.embedding) > 0
            RETURN count(n) AS with_embedding
            CYPHER;
            $embeddingCheckResult = $this->runQuery($embeddingCheckCypher);
            $questionsWithEmbedding = $embeddingCheckResult->first()->get('with_embedding') ?? 0;
            Log::info('[Neo4j Expert] Question nodes with embeddings', ['count' => $questionsWithEmbedding]);
            
            if ($totalQuestions === 0) {
                Log::warning('[Neo4j Expert] No Question nodes exist in the knowledge base. The knowledge base may be empty.');
            } elseif ($questionsWithEmbedding === 0) {
                Log::warning('[Neo4j Expert] Question nodes exist but none have embeddings. Embeddings may need to be generated for the knowledge base.');
            }
        } catch (\Throwable $e) {
            Log::error('[Neo4j Expert] Diagnostic check failed', ['error' => $e->getMessage()]);
        }
        
        $embeddingHash = md5(json_encode($embedding));
        $cacheKey = 'neo4j_expert_context_v2_' . $embeddingHash;
        
        // Check if result is cached
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached !== null) {
            $cachedContextLength = strlen($cached['context'] ?? '');
            $cachedNodeCount = count($cached['relatedNodes'] ?? []);
            
            Log::info('[Neo4j Expert] Using cached result', [
                'cache_key' => $cacheKey,
                'cached_context_length' => $cachedContextLength,
                'cached_node_count' => $cachedNodeCount,
                'cached_context_preview' => mb_substr($cached['context'] ?? '', 0, 300),
                'has_context' => !empty($cached['context'])
            ]);
            
            // If cached result has empty context but nodes exist in DB, invalidate cache and re-query
            if (empty($cached['context']) && $totalQuestions > 0 && $questionsWithEmbedding > 0) {
                Log::warning('[Neo4j Expert] Cached result has empty context but nodes exist - invalidating cache and re-querying', [
                    'total_questions' => $totalQuestions,
                    'questions_with_embedding' => $questionsWithEmbedding
                ]);
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                // Continue to fresh query below
            } else {
                return $cached;
            }
        }
        
        Log::info('[Neo4j Expert] Cache miss - executing fresh query');
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(2), function() use ($embedding) {
            Log::info('[Neo4j Expert] Searching for Question nodes matching user query');
            
            // Re-check inside cache closure (in case database changed)
            $checkCypher = <<<CYPHER
            MATCH (n:Question)
            RETURN count(n) AS total
            CYPHER;
            $checkResult = $this->runQuery($checkCypher);
            $totalQuestions = $checkResult->first()->get('total') ?? 0;
            
            $embeddingCheckCypher = <<<CYPHER
            MATCH (n:Question)
            WHERE n.embedding IS NOT NULL AND size(n.embedding) > 0
            RETURN count(n) AS with_embedding
            CYPHER;
            $embeddingCheckResult = $this->runQuery($embeddingCheckCypher);
            $questionsWithEmbedding = $embeddingCheckResult->first()->get('with_embedding') ?? 0;
            
            if ($totalQuestions === 0) {
                Log::warning('[Neo4j Expert] No Question nodes exist in the knowledge base. The knowledge base may be empty.');
                return [
                    'context' => '',
                    'relatedNodes' => [],
                    'nodeIds' => []
                ];
            }
            
            if ($questionsWithEmbedding === 0) {
                Log::warning('[Neo4j Expert] Question nodes exist but none have embeddings. Embeddings may need to be generated for the knowledge base.');
                return [
                    'context' => '',
                    'relatedNodes' => [],
                    'nodeIds' => []
                ];
            }
            
            // Try with lower threshold first (0.5), then fallback to even lower (0.4) if needed
            $questionResult = $this->expertSearchQuestion($embedding, 0.5);
            
            if (empty($questionResult)) {
                Log::info('[Neo4j Expert] No matches with threshold 0.5, trying lower threshold 0.4');
                $questionResult = $this->expertSearchQuestion($embedding, 0.4);
            }
            
            if (empty($questionResult)) {
                Log::warning('[Neo4j Expert] No matching Question nodes found even with lower threshold. Knowledge base may need more content or better embeddings.');
                return [
                    'context' => '',
                    'relatedNodes' => [],
                    'nodeIds' => []
                ];
            }
            
            $questionNodeId = $questionResult['node']['elementId'];
            $questionName = $questionResult['node']['name'] ?? 'N/A';
            
            Log::info('[Neo4j Expert] Found matching Question node', [
                'elementId' => $questionNodeId,
                'name' => $questionName,
                'score' => round($questionResult['score'] ?? 0, 4)
            ]);
            
            Log::info('[Neo4j Expert] Finding Answer nodes connected to Question', ['questionId' => $questionNodeId]);
            $answerNodes = $this->getAnswerNodesForQuestion($questionNodeId);
            
            if (empty($answerNodes)) {
                Log::warning('[Neo4j Expert] No Answer nodes found for Question', ['questionId' => $questionNodeId]);
                return [
                    'context' => '',
                    'relatedNodes' => [],
                    'nodeIds' => []
                ];
            }
            
            Log::info('[Neo4j Expert] Found Answer nodes', [
                'count' => count($answerNodes),
                'answer_ids' => array_map(function($ans) { return $ans['elementId']; }, $answerNodes)
            ]);
            
            $context = '';
            $allAnswerContent = [];
            $nodeIds = [];
            
            foreach ($answerNodes as $answerNode) {
                $answerContent = $answerNode['content'] ?? '';
                $nodeIds[] = $answerNode['elementId'];
                
                if (!empty($answerContent)) {
                    $allAnswerContent[] = $answerContent;
                    
                    Log::info('[Neo4j Expert] Answer node content', [
                        'elementId' => $answerNode['elementId'],
                        'name' => $answerNode['name'] ?? 'N/A',
                        'content_length' => strlen($answerContent),
                        'content_preview' => mb_substr($answerContent, 0, 200)
                    ]);
                }
            }
            
            if (!empty($allAnswerContent)) {
                $context = implode("\n\n", array_unique($allAnswerContent));
            }
            
            Log::info('[Neo4j Expert] Context built from Answer nodes', [
                'context_length' => strlen($context),
                'answer_count' => count($answerNodes),
                'node_ids' => $nodeIds,
                'context_preview' => mb_substr($context, 0, 500),
                'full_context' => $context // Log full context for debugging
            ]);
            
            // Log each answer node's content for detailed tracking
            foreach ($answerNodes as $idx => $answerNode) {
                Log::info('[Neo4j Expert] Answer node details', [
                    'index' => $idx + 1,
                    'elementId' => $answerNode['elementId'],
                    'name' => $answerNode['name'] ?? 'N/A',
                    'content' => $answerNode['content'] ?? '',
                    'content_length' => strlen($answerNode['content'] ?? '')
                ]);
            }
            
            return [
                'context' => $context,
                'relatedNodes' => $answerNodes,
                'nodeIds' => $nodeIds
            ];
        });
    }

    protected function expertSearchQuestion(array $embedding, float $minScore = 0.65): array
    {
        Log::info('[Neo4j Expert] expertSearchQuestion called - searching Question nodes only');
        $cypher = <<<CYPHER
        MATCH (n:Question)
        WHERE n.embedding IS NOT NULL
        AND size(n.embedding) > 0
        RETURN n AS node
        CYPHER;

        $candidates = $this->runQuery($cypher);
        Log::info('[Neo4j Expert] Found candidate Question nodes', ['count' => $candidates->count()]);

        if ($candidates->count() === 0) {
            Log::warning('[Neo4j Expert] No Question nodes found in expert knowledge base');
            return [];
        }

        $results = [];
        foreach ($candidates as $candidate) {
            $node = $candidate->get('node');
            $props = $node->getProperties()->toArray();
            $nodeArray = $props;
            $nodeArray['elementId'] = $node->getElementId();
            
            $candidateEmbedding = $nodeArray['embedding'];
            if (!$candidateEmbedding || count($candidateEmbedding) === 0) {
                continue;
            }

            $dot = 0.0;
            $normA = 0.0;
            $normB = 0.0;

            foreach ($embedding as $i => $value) {
                $dot += $value * $candidateEmbedding[$i];
                $normA += $value * $value;
                $normB += $candidateEmbedding[$i] * $candidateEmbedding[$i];
            }

            $score = $dot / (sqrt($normA) * sqrt($normB));

            if ($score >= $minScore) {
                $results[] = [
                    'node' => $nodeArray,
                    'score' => $score
                ];
            }
        }

        Log::info('[Neo4j Expert] Question nodes found matching query (score >= ' . $minScore . ')', [
            'matching_count' => count($results),
            'questions' => array_map(function($result) {
                return [
                    'elementId' => $result['node']['elementId'] ?? 'unknown',
                    'name' => $result['node']['name'] ?? 'N/A',
                    'score' => round($result['score'], 4)
                ];
            }, $results)
        ]);

        usort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        if (!empty($results)) {
            $bestMatch = $results[0];
            Log::info('[Neo4j Expert] Best matching Question node selected', [
                'elementId' => $bestMatch['node']['elementId'] ?? 'unknown',
                'name' => $bestMatch['node']['name'] ?? 'N/A',
                'score' => round($bestMatch['score'], 4)
            ]);
        }

        return !empty($results) ? $results[0] : [];
    }

    protected function getAnswerNodesForQuestion(string $questionElementId): array
    {
        Log::info('[Neo4j Expert] getAnswerNodesForQuestion called', ['questionId' => $questionElementId]);
        
        $cypher = <<<CYPHER
        MATCH (q:Question)
        WHERE elementId(q) = \$questionId
        OPTIONAL MATCH (q)-[:HAS_ANSWER]->(ans:Answer)
        RETURN DISTINCT ans AS answer
        CYPHER;

        $result = $this->runQuery($cypher, ['questionId' => $questionElementId]);
        
        Log::info('[Neo4j Expert] Answer nodes query executed', ['found_count' => $result->count()]);
        
        if ($result->count() === 0) {
            Log::warning('[Neo4j Expert] No Answer nodes found for Question via HAS_ANSWER relationship');
            return [];
        }

        $answerNodes = [];
        foreach ($result as $row) {
            $answerNode = $row->get('answer');
            $props = $answerNode->getProperties()->toArray();
            
            $answerContent = $props['content'] ?? '';
            
            Log::info('[Neo4j Expert] Answer node properties retrieved', [
                'elementId' => $answerNode->getElementId(),
                'name' => $props['name'] ?? 'N/A',
                'has_content' => !empty($answerContent),
                'content_length' => strlen($answerContent),
                'content_preview' => mb_substr($answerContent, 0, 500),
                'all_properties' => array_keys($props),
                'full_content' => $answerContent
            ]);
            
            $answerNodes[] = [
                'elementId' => $answerNode->getElementId(),
                'name' => $props['name'] ?? '',
                'content' => $answerContent
            ];
        }

        return $answerNodes;
    }

    protected function getExpertRelatedContent(string $elementId): array
    {
        Log::info('[Neo4j Expert] Getting related content for node', ['elementId' => $elementId]);
        
        $cypher = <<<CYPHER
        MATCH (n)
        WHERE elementId(n) = \$id
        
        OPTIONAL MATCH (n)-[:HAS_CATEGORY]->(cat:Category)
        OPTIONAL MATCH (n)-[:HAS_QUESTION]->(q:Question)
        OPTIONAL MATCH (n)-[:HAS_ANSWER]->(ans:Answer)
        OPTIONAL MATCH (n)-[:HAS_COMPONENT]->(comp:Component)
        OPTIONAL MATCH (n)-[:HAS_TOOLS]->(tool:Tools)
        
        OPTIONAL MATCH (cat)-[:HAS_QUESTION]->(catQ:Question)
        OPTIONAL MATCH (q)-[:HAS_ANSWER]->(qAns:Answer)
        OPTIONAL MATCH (comp)-[:HAS_TOOLS]->(compTool:Tools)
        
        WITH collect(DISTINCT cat) + collect(DISTINCT q) + collect(DISTINCT ans) + 
             collect(DISTINCT comp) + collect(DISTINCT tool) + collect(DISTINCT catQ) + 
             collect(DISTINCT qAns) + collect(DISTINCT compTool) as allRelated
        
        UNWIND allRelated as related
        WITH related
        WHERE related IS NOT NULL
        RETURN DISTINCT related, labels(related) as nodeLabels
        LIMIT 20
        CYPHER;

        $result = $this->runQuery($cypher, ['id' => $elementId]);
        
        Log::info('[Neo4j Expert] Related nodes query executed', ['found_count' => $result->count()]);
        
        if ($result->count() === 0) {
            Log::warning('[Neo4j Expert] No related nodes found via HAS_CATEGORY, HAS_QUESTION, HAS_ANSWER, HAS_COMPONENT, HAS_TOOLS relationships');
            return [];
        }

        $relatedNodes = [];
        $relatedNodeDetails = [];
        foreach ($result as $row) {
            $node = $row->get('related');
            $labels = $row->get('nodeLabels');
            
            $nodeType = 'Unknown';
            $labelArray = [];
            if ($labels instanceof \Laudis\Neo4j\Types\CypherList) {
                $labelArray = $labels->toArray();
                if (!empty($labelArray)) {
                    $nodeType = $labelArray[0];
                }
            }
            
            $props = $node->getProperties()->toArray();
            $relatedNodes[] = [
                'elementId' => $node->getElementId(),
                'type' => $nodeType,
                'name' => $props['name'] ?? '',
                'content' => $props['content'] ?? $props['content'] ?? '',
            ];
            
            $relatedNodeDetails[] = [
                'type' => $nodeType,
                'labels' => $labelArray,
                'name' => $props['name'] ?? 'N/A',
                'elementId' => $node->getElementId()
            ];
        }

        Log::info('[Neo4j Expert] Related nodes retrieved', [
            'count' => count($relatedNodes),
            'nodes' => $relatedNodeDetails
        ]);

        return $relatedNodes;
    }
}