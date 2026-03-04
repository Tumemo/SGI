import { useEffect } from "react"
import BannerGlobal from "../Componentes/BannerGlobal/BannerGlobal"
import NavBar from "../Componentes/NavBar/NavBar"
import CardRanking from "../Componentes/CardRanking/CardRanking"
import DadosJson from "../assets/json/DadosDB.json"

function Ranking(){
    useEffect(()=>{
        document.title = "SGM - Ranking"
    },[])
    return(
        <>
            <BannerGlobal titulo="Ranking" voltar="/Home"/>
            <main className="w-full flex flex-col items-center my-5 pb-20">
                <div className="w-[80%] flex flex-col gap-4 items-start">
                    <CardRanking props={{posicao: "1", nome: "Sala A", pontos: "1500"}} />
                    <CardRanking props={{posicao: "2", nome: "Sala B", pontos: "1200"}} />
                    <CardRanking props={{posicao: "3", nome: "Sala C", pontos: "900"}} />
                    <CardRanking props={{posicao: "4", nome: "Sala D", pontos: "800"}} />
                    <CardRanking props={{posicao: "5", nome: "Sala E", pontos: "700"}} />
                </div>
            </main>
            <NavBar />
        </>
    )
}

export default Ranking