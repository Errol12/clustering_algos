public function getallnode_degree()
    {
        $client = new \Everyman\Neo4j\Client();
        //$servers=DB::connection('neo4j')->select('MATCH (n:Servers) RETURN n LIMIT 25');

        /*$servers=DB::connection('neo4j')->select('MATCH (n:Servers)-[r]-()
                                                    return n.hostname, count(distinct r) as degree
                                                    order by degree desc limit 10
                                                    ');*/

        /*
        --Using betweenes algo
        $servers=DB::connection('neo4j')->select('CALL algo.betweenness.stream("Servers","TALKS_TO",{direction:"out"})
                                                    YIELD nodeId , centrality
                                                    RETURN nodeId,centrality order by centrality desc limit 10;');                                          
        foreach($servers as $server)
        {   
            $s_id = $server[0];
            $s_name=DB::connection('neo4j')->select('MATCH (n:Servers) WHERE ID(n) = '.$s_id.' RETURN n.hostname');

            $str="MATCH (n:Servers) WHERE n.hostname IN ['".$s_name[0][0]."'] SET n.betweeness_1 = ".$server[1]." RETURN n";
            $servers=DB::connection('neo4j')->select($str);
        }

        return 1;*/

        
        
        //To set all cluster id and node size to zero of all servers
        /*$i = 0;
        $all_servers = DB::connection('neo4j')->select('MATCH (n:Servers) RETURN n.hostname');

        foreach($all_servers as $server)
        {
            $server_cluster=DB::connection('neo4j')->select("MATCH (n:Servers) WHERE n.hostname IN ['".$server[0]."'] SET n.cluster_1 = ".$i." RETURN n");
            $size = mt_rand(1,20);
            $server_size=DB::connection('neo4j')->select("MATCH (n:Servers) WHERE n.hostname IN ['".$server[0]."'] SET n.size_1 = ".$size." RETURN n");
        }
        return 1;*/

        /*
        --To sort by asc degree and then assign directly connected nodes to same cluster
        $servers=DB::connection('neo4j')->select('MATCH (n:Servers)-[r]-()
                                                    return n.hostname, count(distinct r) as degree
                                                    order by degree asc 
                                                    ');
        $cluster_grp = 1;
        foreach($servers as $server)
        {
            $servers=DB::connection('neo4j')->select("MATCH (n:Servers) WHERE n.hostname IN ['".$server[0]."'] SET n.cluster_1 = ".$cluster_grp." RETURN n");
            $s = DB::connection('neo4j')->select("Match (n:Servers {hostname:'".$server[0]."'})-[r:TALKS_TO]-(l:Servers) SET l.cluster_1=n.cluster_1 return l");
            $cluster_grp = $cluster_grp + 1;
        }
        return 1;*/


        /*//--To sort by asc degree and then assign degree to each node
        $servers=DB::connection('neo4j')->select('MATCH (n:Servers)-[r]-()
                                                    return n.hostname, count(distinct r) as degree
                                                    order by degree asc 
                                                    ');
        $cluster_grp = 1;
        foreach($servers as $server)
        {
            $servers=DB::connection('neo4j')->select("MATCH (n:Servers) WHERE n.hostname IN ['".$server[0]."'] SET n.cluster_1 = ".$cluster_grp." RETURN n");
            $s = DB::connection('neo4j')->select("Match (n:Servers {hostname:'".$server[0]."'})-[r:TALKS_TO]-(l:Servers) SET l.cluster_1=n.cluster_1 return l");
            $cluster_grp = $cluster_grp + 1;
        }
        return 1;*/
        


        //Sort by degree desc --then asign only non cluster associated nodes to parent degree cluster
        $servers=DB::connection('neo4j')->select('MATCH (n:Servers)-[r]-()
                                                    return n.hostname,n.size_1,count(distinct r) as degree
                                                    order by degree desc 
                                                    ');
        $cluster_grp = 1;
        foreach($servers as $server)
        {   
            $host_cluster = DB::connection('neo4j')->select("MATCH (n:Servers) WHERE n.hostname IN ['".$server[0]."'] RETURN n.cluster_1");
            $cluster_size = 0;
            $host_name = $server[0];

            if($host_cluster[0][0] == '0')
            {   //echo 'big_if';
                //echo 'Parent node = '.$server[0].' and cluster = '.$host_cluster[0][0];echo '<br>';
                echo 'Degree of node '.$host_name.' is '.$server[2];echo '<br>';


                $parent_server=DB::connection('neo4j')->select("MATCH (n:Servers) WHERE n.hostname IN ['".$server[0]."'] SET n.cluster_1 = ".$cluster_grp." RETURN n");

                //Create a cluster node
                

                //Create relationship between Cluster and parent server
                
               
                
                                          
                                    
                    
                $connected_nodes = DB::connection('neo4j')->select('Match (n:Servers {hostname:"'.$server[0].'"})-[r:TALKS_TO]-(l:Servers) return l.hostname,l.cluster_1');

                
                $node_counter = 0;
                $conn_nodes_array = array();
                foreach($connected_nodes as $connected_node)
                {   
                    if($connected_node[1] == '0')
                    {   
                        $set_child_server_cluster = DB::connection('neo4j')->select("MATCH (n:Servers) WHERE n.hostname IN ['".$connected_node[0]."'] SET n.cluster_1 = ".$cluster_grp." RETURN n.cluster_1");

                        $child_node = DB::connection('neo4j')->select("MATCH (n:Servers) WHERE n.hostname IN ['".$connected_node[0]."'] RETURN n.size_1");

                        /*$cluster_node = DB::connection('neo4j')->select("CREATE (app:Clusters {name: ".$cluster_grp."}) RETURN app.name");*/
                        $cluster_node = DB::connection('neo4j')->select("MATCH (app:Clusters {name: ".$cluster_grp."}) RETURN app.name");
                        if(sizeof($cluster_node) == 0)
                        {
                            $cluster_node = DB::connection('neo4j')->select("CREATE (app:Clusters {name: ".$cluster_grp."}) RETURN app.name");
                        }
                    

                        $cluster_child_relation = DB::connection('neo4j')
                                    ->select('MATCH (s:Servers),(c:Clusters)
                                                WHERE s.hostname = "'.$connected_node[0].'" AND (c.name = '.$cluster_node[0][0].')
                                                CREATE (s)-[r:BELONGS_TO]->(c) RETURN r');  

                        
                        $cluster_size = $cluster_size + $child_node[0][0];
                        $set_cluster_size = DB::connection('neo4j')->select("MATCH (n:Clusters) WHERE n.name IN [".$cluster_node[0][0]."] SET n.size_1 = ".$cluster_size." RETURN n");

                        //echo 'Node: '.$connected_node[0].' added to '.$cluster_node[0][0];echo '<br>';
                        $conn_nodes_array[$connected_node[0]] = $cluster_node[0][0];
                    }
                    else
                    {
                        $node_counter = $node_counter + 1;
                    }

                }

                $cluster_size = $cluster_size + $server[1];

                if($node_counter == sizeof($connected_nodes))
                {   
                    
                    $cluster_id = ServerController::asignNeighbouringCluster($host_name);
                    /*echo 'the host name is '.$server[0].' initially assigned cluster is '.$cluster_grp.' newly assigned cluster is '.$cluster_id;echo '<br>';*/
                    //print_r($cluster_id);
                    //return 'end';
                    $parent_server=DB::connection('neo4j')->select('MATCH (n:Servers) WHERE n.hostname = "'.$server[0].'" SET n.cluster_1 = '.$cluster_id.' RETURN n');
                    $cluster_parent_relation = DB::connection('neo4j')
                                    ->select('MATCH (s:Servers),(c:Clusters)
                                                WHERE s.hostname = "'.$server[0].'" AND (c.name = '.$cluster_id.')
                                                CREATE (s)-[r:BELONGS_TO]->(c) RETURN r');  

                    $set_cluster_size = DB::connection('neo4j')->select("MATCH (n:Clusters) WHERE n.name IN ['".$cluster_id."'] SET n.size_1 = ".$cluster_size." RETURN n");
                    echo 'Parent Node: '.$server[0].' added to '.$cluster_id;echo '<br>';
                }
                else
                {   //echo 'trigerred';
                    $cluster_parent_relation = DB::connection('neo4j')
                                    ->select('MATCH (s:Servers),(c:Clusters)
                                                WHERE s.hostname = "'.$server[0].'" AND (c.name = '.$cluster_node[0][0].')
                                                CREATE (s)-[r:BELONGS_TO]->(c) RETURN r');  
                    $set_cluster_size = DB::connection('neo4j')->select("MATCH (n:Clusters) WHERE n.name IN ['".$cluster_node[0][0]."'] SET n.size_1 = ".$cluster_size." RETURN n");
                    $cluster_grp = $cluster_grp + 1;
                    echo 'Parent Node: '.$server[0].' added to '.$cluster_node[0][0];echo '<br>';
                }



                foreach ($conn_nodes_array as $key => $value) {
                   echo 'Node: '.$key.' added to '.$value;echo '<br>';
                }

                echo 'Cluster '.$cluster_node[0][0].' ended';
                echo '<br>';echo '<br>';
                
                
            }
            
            //break;
            
        }

        return 1;


        


        
    }

    public static function asignNeighbouringCluster($server_node)
    {
        //echo 'for server node '.$server_node;echo '<br>';
        $connected_nodes = DB::connection('neo4j')->select('Match (n:Servers {hostname:"'.$server_node.'"})-[r:TALKS_TO]-(l:Servers) return l.cluster_1');
        $cluster_name = 0;
        $first_cluster_size_query = DB::connection('neo4j')->select('MATCH (n:Clusters) WHERE n.name  = '.$connected_nodes[0][0].' RETURN n.size_1');

        //echo 'connected_cluster '.$connected_nodes[0][0];
        //echo '<br>';
        $min_size = $first_cluster_size_query[0][0];
        $cluster_name = $connected_nodes[0][0];
        //echo 'first cluster size is '.$first_cluster_size_query[0][0].' therefore min size is '.$min_size;echo '<br>';
        
        
        //return 'hi';
        foreach($connected_nodes as $connected_node)
        {
            $cluster_size = DB::connection('neo4j')->select('MATCH (n:Clusters) WHERE n.name  = '.$connected_node[0].' RETURN n.size_1');
            //echo 'Cluster size is '.$cluster_size[0][0].' and name is '.$connected_node[0];
            //echo '<br>';

            if($min_size > $cluster_size[0][0])
            {
                $min_size = $cluster_size[0][0];
                $cluster_name = $connected_node[0];
            }
            //$cluster[$connected_node] = $cluster_size[0][0];
        }
        

        /*foreach($cluster as $clus)
        {

        }*/
        return $cluster_name;
    }
